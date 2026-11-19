<?php

namespace Tests\Feature\Resend;

use App\Jobs\GuardScheduledCampaign;
use App\Jobs\SyncCampaignAudience;
use App\Models\EmailAuditLog;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSuppression;
use App\Models\MarketingContact;
use App\Models\OutboundEmail;
use App\Models\User;
use App\Services\Resend\CampaignService;
use App\Services\Resend\ResendException;
use App\Services\Resend\ResendQuota;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Phase 5.1: a recipient who opts out or is suppressed AFTER a campaign was approved
 * must never be emailed. Resend does not document when a scheduled broadcast resolves
 * its segment, so a scheduled broadcast is deleted (documented to cancel delivery)
 * instead of trying to edit its audience.
 */
class ScheduledSuppressionSafetyTest extends ResendTestCase
{
    private function campaign(User $user, array $attrs = []): EmailCampaign
    {
        [, , $sender] = $this->connectResend($user);

        return EmailCampaign::create(array_merge([
            'owner_user_id' => $user->id, 'created_by_user_id' => $user->id, 'sender_id' => $sender->id,
            'name' => 'November news', 'subject' => 'News', 'body' => "Hi {{first_name|there}},\n\nNews.",
            'status' => 'draft', 'segment_filters' => [],
        ], $attrs));
    }

    private function resendAccepts(): void
    {
        $this->respondWith(self::API . '/segments', fn () => Http::response(['object' => 'segment', 'id' => 'seg_1', 'name' => 'x']));
        $this->respondWith(self::API . '/contacts', fn () => Http::response(['object' => 'contact', 'id' => 'ct_' . Str::random(5)]));
        $this->respondWith(self::API . '/domains/dom_1', fn () => Http::response($this->domainPayload()));
        $this->respondWith(self::API . '/broadcasts', fn () => Http::response(['object' => 'broadcast', 'id' => 'bc_1']));
    }

    /** Approve a campaign for tomorrow with the given contacts; Resend accepts everything. */
    private function scheduled(User $user, array $emails = ['a@biz.example', 'b@biz.example']): EmailCampaign
    {
        $c = $this->campaign($user);
        foreach ($emails as $email) {
            $this->contact($user, $email);
        }
        $this->resendAccepts();
        $this->actingAs($user)->post("/user/campaigns/{$c->id}/approve", [
            'confirm' => '1', 'confirm_consent' => '1', 'send_mode' => 'schedule',
            'scheduled_at' => now()->addDay()->startOfHour()->format('Y-m-d H:i'),
            'expected_count' => count($emails),
        ])->assertSessionHas('success');
        $this->assertSame('scheduled', $c->fresh()->status);

        return $c->fresh();
    }

    private function resendDeletes(mixed $response = null): void
    {
        $this->respondWith(self::API . '/broadcasts/bc_1', $response ?? fn () => Http::response(['object' => 'broadcast', 'id' => 'bc_1', 'deleted' => true]));
    }

    private function assertAutoCancelled(EmailCampaign $c, User $user): void
    {
        $c->refresh();
        $this->assertCount(1, $this->resendCalls('DELETE', '/broadcasts/bc_1'), 'The scheduled broadcast is deleted at Resend');
        $this->assertSame('cancelled', $c->status);
        $this->assertStringContainsString('Automatically cancelled: 1 recipient(s)', $c->last_error);
        $this->assertSame(0, OutboundEmail::where('campaign_id', $c->id)->where('status', '!=', 'cancelled')->count(), 'Nobody is marked as sent');
        $this->assertSame(0, app(ResendQuota::class)->used($user, ResendQuota::MONTHLY));
        $this->assertSame(0, app(ResendQuota::class)->used($user, ResendQuota::CAMPAIGNS), 'Unsent campaign does not use the monthly allowance');
        $this->assertTrue(EmailAuditLog::where('action', 'campaign_auto_cancelled')->exists());
        // Even long after the send time, maintenance never marks it as sent
        $this->travel(3)->days();
        $this->artisan('resend:maintain')->assertSuccessful();
        $this->assertSame('cancelled', $c->fresh()->status);
    }

    public function test_manual_do_not_email_entry_cancels_the_scheduled_broadcast(): void
    {
        $user = $this->resendUser();
        $c = $this->scheduled($user);
        $this->resendDeletes();

        $this->actingAs($user)->post('/user/email-sending/suppressions', ['email' => 'B@Biz.example'])->assertSessionHasNoErrors();

        $this->assertAutoCancelled($c, $user);
        $this->assertSame('skipped', EmailCampaignRecipient::where('email', 'b@biz.example')->value('sync_status'));
        $this->assertSame('synced', EmailCampaignRecipient::where('email', 'a@biz.example')->value('sync_status'));
    }

    public function test_contact_withdrawal_cancels_the_scheduled_broadcast(): void
    {
        $user = $this->resendUser();
        $c = $this->scheduled($user);
        $this->resendDeletes();
        $contact = MarketingContact::where('email', 'a@biz.example')->sole();

        $this->actingAs($user)->post("/user/campaigns/contacts/{$contact->id}/withdraw")->assertSessionHas('success');

        $this->assertAutoCancelled($c, $user);
    }

    public function test_gmail_unsubscribe_link_cancels_the_scheduled_broadcast(): void
    {
        $user = $this->resendUser();
        $c = $this->scheduled($user, ['info@smiledental.example', 'b@biz.example']);
        $this->resendDeletes();
        // The same person also received a one-to-one Gmail email and clicks its unsubscribe link
        $this->connect($user);
        $gmail = $this->sendDraft($user, $this->draftFor($user));
        $this->assertSame(['gmail', 'info@smiledental.example'], [$gmail->provider, $gmail->to_email]);

        $this->post(URL::signedRoute('email.unsubscribe.confirm', ['email' => $gmail->id]))->assertOk();

        $this->assertAutoCancelled($c, $user);
    }

    public function test_resend_contact_unsubscribe_webhook_cancels_the_scheduled_broadcast(): void
    {
        $this->assertWebhookCancels(fn () => $this->event('contact.updated', ['id' => 'ct_1', 'email' => 'a@biz.example', 'unsubscribed' => true]));
    }

    public function test_hard_bounce_from_another_resend_email_cancels_the_scheduled_broadcast(): void
    {
        $this->assertWebhookCancels(fn () => $this->event('email.bounced', [
            'email_id' => 're_other', 'to' => ['a@biz.example'], 'bounce' => ['type' => 'Permanent', 'subType' => 'General'],
        ]));
    }

    private function assertWebhookCancels(\Closure $payload): void
    {
        $user = $this->resendUser();
        $c = $this->scheduled($user);
        $this->resendDeletes();
        [$config, $secret] = $this->webhookFor($c->integration);
        // An earlier one-to-one Resend email to the same person (the bounce refers to it)
        OutboundEmail::create([
            'owner_user_id' => $user->id, 'provider' => 'resend', 'user_id' => $user->id,
            'idempotency_key' => (string) Str::uuid(), 'from_email' => 'hello@mail.pixelstudio.example', 'to_email' => 'a@biz.example',
            'subject' => 'Hi', 'body' => 'Body', 'status' => 'sent', 'provider_message_id' => 're_other', 'queued_at' => now(), 'sent_at' => now(),
        ]);

        $this->postWebhook($config, $secret, $payload())->assertOk();

        $this->assertAutoCancelled($c, $user);
    }

    public function test_unrelated_suppressions_and_other_accounts_never_touch_the_campaign(): void
    {
        $user = $this->resendUser();
        $c = $this->scheduled($user);
        $otherOwner = $this->resendUser([], ['email' => 'someone-else@agency.example']);

        EmailSuppression::suppress($user->id, 'stranger@biz.example', EmailSuppression::REASON_MANUAL);
        EmailSuppression::suppress($otherOwner->id, 'a@biz.example', EmailSuppression::REASON_UNSUBSCRIBED); // another tenant
        $this->artisan('resend:maintain')->assertSuccessful();

        $this->assertCount(0, $this->resendCalls('DELETE'));
        $this->assertSame('scheduled', $c->fresh()->status);
        $this->assertSame(2, OutboundEmail::where('campaign_id', $c->id)->where('status', 'pending')->count());
    }

    public function test_maintenance_sweep_catches_suppressions_written_outside_the_normal_path(): void
    {
        $user = $this->resendUser();
        $c = $this->scheduled($user);
        $this->resendDeletes();
        // e.g. imported directly into the table, or a contact edited without suppress()
        MarketingContact::where('email', 'a@biz.example')->update(['consent_status' => MarketingContact::WITHDRAWN]);
        $this->assertCount(0, $this->resendCalls('DELETE'));

        $this->artisan('resend:maintain')->assertSuccessful();

        $this->assertAutoCancelled($c, $user);
    }

    public function test_when_resend_refuses_the_cancellation_the_campaign_needs_attention(): void
    {
        $user = $this->resendUser();
        $c = $this->scheduled($user);
        $this->resendDeletes(fn () => Http::response(['statusCode' => 422, 'name' => 'validation_error', 'message' => 'Broadcast is already being sent'], 422));

        EmailSuppression::suppress($user->id, 'a@biz.example', EmailSuppression::REASON_MANUAL);

        $c->refresh();
        $this->assertSame('unknown', $c->status);
        $this->assertStringContainsString('did not accept the cancellation', $c->last_error);
        $this->assertTrue(EmailAuditLog::where('action', 'campaign_auto_cancel_failed')->exists());
        $this->actingAs($user)->get("/user/campaigns/{$c->id}")->assertOk()->assertSee('delete it if it is still scheduled');
    }

    public function test_network_errors_are_retried_and_finally_flagged(): void
    {
        $user = $this->resendUser();
        $c = $this->scheduled($user);
        $this->resendDeletes(new ConnectionException('timeout'));
        Queue::fake();
        EmailSuppression::suppress($user->id, 'a@biz.example', EmailSuppression::REASON_MANUAL);
        Queue::assertPushed(GuardScheduledCampaign::class, fn ($job) => $job->campaignId === $c->id);

        try {
            app()->call([new GuardScheduledCampaign($c->id), 'handle']);
            $this->fail('A network error must be thrown so the queue retries');
        } catch (ResendException $e) {
            $this->assertTrue($e->isAmbiguous());
        }
        $this->assertSame('scheduled', $c->fresh()->status, 'Not marked cancelled while unconfirmed');

        (new GuardScheduledCampaign($c->id))->failed(null);
        $this->assertSame('unknown', $c->fresh()->status);
    }

    public function test_contact_suppressed_after_sync_but_before_broadcast_is_removed_from_the_segment(): void
    {
        config(['resend_user.sync_batch' => 1]);
        $user = $this->resendUser();
        $c = $this->campaign($user);
        $this->contact($user, 'a@biz.example');
        $this->contact($user, 'b@biz.example');
        $this->resendAccepts();
        $this->respondWith(self::API . '/contacts/a%40biz.example/segments/seg_1', fn () => Http::response(['id' => 'seg_1', 'deleted' => true]));

        Queue::fake();
        $this->actingAs($user)->post("/user/campaigns/{$c->id}/approve", [
            'confirm' => '1', 'confirm_consent' => '1', 'send_mode' => 'now', 'expected_count' => 2,
        ])->assertSessionHas('success');

        app()->call([new SyncCampaignAudience($c->id), 'handle']);           // a@ added to the segment
        $this->assertSame('synced', EmailCampaignRecipient::where('email', 'a@biz.example')->value('sync_status'));
        EmailSuppression::suppress($user->id, 'a@biz.example', EmailSuppression::REASON_UNSUBSCRIBED);
        app()->call([new SyncCampaignAudience($c->id), 'handle']);           // b@ added, a@ removed, broadcast created

        $this->assertCount(1, $this->resendCalls('DELETE', '/contacts/a%40biz.example/segments/seg_1'));
        $this->assertCount(1, $this->resendCalls('POST', '/broadcasts'));
        $removedAt = array_search($this->resendCalls('DELETE', '/contacts/a%40biz.example/segments/seg_1')[0], $this->google, true);
        $broadcastAt = array_search($this->resendCalls('POST', '/broadcasts')[0], $this->google, true);
        $this->assertLessThan($broadcastAt, $removedAt, 'Removed before the broadcast exists');
        $this->assertSame('cancelled', OutboundEmail::where('to_email', 'a@biz.example')->value('status'));
        $this->assertSame('sent', OutboundEmail::where('to_email', 'b@biz.example')->value('status'));
        $this->assertSame(1, app(ResendQuota::class)->used($user, ResendQuota::MONTHLY));
    }

    public function test_failed_segment_removal_never_creates_the_broadcast(): void
    {
        config(['resend_user.sync_batch' => 1]);
        $user = $this->resendUser();
        $c = $this->campaign($user);
        $this->contact($user, 'a@biz.example');
        $this->contact($user, 'b@biz.example');
        $this->resendAccepts();
        $this->respondWith(self::API . '/contacts/a%40biz.example/segments/seg_1', fn () => Http::response(['name' => 'internal_server_error'], 500));

        Queue::fake();
        $this->actingAs($user)->post("/user/campaigns/{$c->id}/approve", [
            'confirm' => '1', 'confirm_consent' => '1', 'send_mode' => 'now', 'expected_count' => 2,
        ])->assertSessionHas('success');
        app()->call([new SyncCampaignAudience($c->id), 'handle']);
        EmailSuppression::suppress($user->id, 'a@biz.example', EmailSuppression::REASON_MANUAL);

        try {
            app()->call([new SyncCampaignAudience($c->id), 'handle']);
            $this->fail('Expected a retry');
        } catch (ResendException) {
        }
        (new SyncCampaignAudience($c->id))->failed(null);

        $this->assertCount(0, $this->resendCalls('POST', '/broadcasts'));
        $this->assertSame('failed', $c->fresh()->status);
        $this->assertSame(0, OutboundEmail::where('campaign_id', $c->id)->where('status', 'sent')->count());
    }
}
