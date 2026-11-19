<?php

namespace Tests\Feature\Resend;

use App\Models\EmailCampaign;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Models\User;
use App\Services\EmailTracking\EmailAnalytics;
use App\Services\Resend\ResendQuota;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/** Phase 5: Broadcast campaigns and unified Gmail + Resend analytics. */
class CampaignAndAnalyticsTest extends ResendTestCase
{
    private function campaign(User $user, array $attrs = []): EmailCampaign
    {
        [, , $sender] = $this->connectResend($user);

        return EmailCampaign::create(array_merge([
            'owner_user_id' => $user->id, 'created_by_user_id' => $user->id, 'sender_id' => $sender->id,
            'name' => 'November news', 'subject' => 'News for {{first_name|you}}', 'body' => "Hi {{first_name|there}},\n\nOur new booking page: https://pixelstudio.example/booking",
            'status' => 'draft', 'segment_filters' => [],
        ], $attrs));
    }

    /** Fake the whole broadcast flow. */
    private function resendAccepts(bool $contactExists = false, $broadcast = null): void
    {
        $this->respondWith(self::API . '/segments', fn () => Http::response(['object' => 'segment', 'id' => 'seg_1', 'name' => 'x']));
        $this->respondWith(self::API . '/contacts', fn () => $contactExists
            ? Http::response(['statusCode' => 422, 'name' => 'validation_error', 'message' => 'Contact already exists'], 422)
            : Http::response(['object' => 'contact', 'id' => 'ct_' . Str::random(5)]));
        $this->respondWith(self::API . '/domains/dom_1', fn () => Http::response($this->domainPayload()));
        $this->respondWith(self::API . '/broadcasts', $broadcast ?? fn () => Http::response(['object' => 'broadcast', 'id' => 'bc_1']));
    }

    private function approve(User $user, EmailCampaign $c, array $extra = [])
    {
        return $this->actingAs($user)->post("/user/campaigns/{$c->id}/approve", array_merge([
            'confirm' => '1', 'confirm_consent' => '1', 'send_mode' => 'now', 'expected_count' => app(\App\Services\Resend\CampaignService::class)->eligible($c)->count(),
        ], $extra));
    }

    public function test_review_counts_only_opted_in_unsuppressed_contacts_matching_filters(): void
    {
        $user = $this->resendUser();
        $c = $this->campaign($user, ['segment_filters' => ['tag' => 'vip']]);
        $this->contact($user, 'a@biz.example', ['tags' => ['vip']]);
        $this->contact($user, 'b@biz.example', ['tags' => ['vip']]);
        $this->contact($user, 'c@biz.example', ['tags' => ['other']]);
        $this->contact($user, 'd@biz.example', ['tags' => ['vip'], 'unsubscribed_at' => now()]);
        $this->contact($user, 'e@biz.example', ['tags' => ['vip'], 'consent_evidence' => null]);
        EmailSuppression::suppress($user->id, 'b@biz.example', 'manual');
        $this->leadFor($user, ['email' => 'lead@biz.example']); // leads are never recipients

        $review = app(\App\Services\Resend\CampaignService::class)->review($user, $c);
        $this->assertSame(1, $review['eligible']);
        $this->assertSame(1, $review['excluded_suppressed']);
        $this->assertSame('News for Sara', $review['subject']);

        $this->actingAs($user)->get("/user/campaigns/{$c->id}")->assertOk()->assertSee('1</strong> eligible', false)
            ->assertSee('Unsubscribe: [Resend unsubscribe link]');
    }

    public function test_test_email_goes_only_to_the_signed_in_user(): void
    {
        $user = $this->resendUser();
        $c = $this->campaign($user);
        $this->contact($user);
        $this->respondWith(self::API . '/emails', fn () => Http::response(['id' => 're_test']));

        $this->actingAs($user)->post("/user/campaigns/{$c->id}/test")->assertSessionHas('success');

        $data = $this->resendCalls('POST', '/emails')[0]->data();
        $this->assertSame([$user->email], $data['to']);
        $this->assertStringStartsWith('[TEST] ', $data['subject']);
        $this->assertSame(1, $c->fresh()->test_sends);
        $this->assertSame(0, OutboundEmail::count());
    }

    public function test_approval_sends_one_broadcast_to_a_campaign_segment(): void
    {
        $user = $this->resendUser();
        $c = $this->campaign($user);
        $this->contact($user, 'a@biz.example');
        $this->contact($user, 'b@biz.example', ['first_name' => null]);
        $this->resendAccepts(true); // contacts already exist in Resend -> added to the segment instead

        $this->approve($user, $c, ['expected_count' => 5])->assertSessionHas('error'); // count changed since review
        $this->assertSame('draft', $c->fresh()->status);

        $this->respondWith(self::API . '/contacts/a%40biz.example/segments/seg_1', fn () => Http::response(['id' => 'seg_1']));
        $this->respondWith(self::API . '/contacts/b%40biz.example/segments/seg_1', fn () => Http::response(['id' => 'seg_1']));
        $this->approve($user, $c)->assertSessionHas('success');

        $c->refresh();
        $this->assertSame('submitted', $c->status);
        $this->assertSame('bc_1', $c->provider_broadcast_id);
        $this->assertCount(1, $this->resendCalls('POST', '/broadcasts'));
        $b = $this->resendCalls('POST', '/broadcasts')[0]->data();
        $this->assertSame('seg_1', $b['segment_id']);
        $this->assertTrue($b['send']);
        $this->assertStringContainsString('{{{contact.first_name|there}}}', $b['text']);
        $this->assertStringContainsString('{{{RESEND_UNSUBSCRIBE_URL}}}', $b['text']);
        $this->assertStringContainsString('12 Mall Road', $b['text'], 'Postal address included');
        $this->assertStringContainsString('<a href="{{{RESEND_UNSUBSCRIBE_URL}}}">', $b['html']);
        $this->assertSame(2, OutboundEmail::where('campaign_id', $c->id)->where('status', 'sent')->count());
        $this->assertSame(2, app(ResendQuota::class)->used($user, ResendQuota::MONTHLY));
        $this->assertSame(1, app(ResendQuota::class)->used($user, ResendQuota::CAMPAIGNS));

        // A second approval of the same campaign does nothing
        $this->approve($user, $c)->assertSessionHas('error');
        $this->assertCount(1, $this->resendCalls('POST', '/broadcasts'));
    }

    public function test_contact_who_opts_out_before_sync_is_skipped(): void
    {
        $user = $this->resendUser();
        $c = $this->campaign($user);
        $this->contact($user, 'a@biz.example');
        $this->contact($user, 'b@biz.example');
        $this->resendAccepts();
        // b unsubscribes right after approval, before the sync job adds contacts
        \Illuminate\Support\Facades\Queue::fake();
        $this->approve($user, $c)->assertSessionHas('success');
        EmailSuppression::suppress($user->id, 'b@biz.example', 'unsubscribed');

        app()->call([new \App\Jobs\SyncCampaignAudience($c->id), 'handle']);

        $this->assertCount(1, $this->resendCalls('POST', '/contacts'));
        $this->assertSame('a@biz.example', $this->resendCalls('POST', '/contacts')[0]->data()['email']);
        $skipped = OutboundEmail::where('to_email', 'b@biz.example')->sole();
        $this->assertSame('cancelled', $skipped->status);
        $this->assertSame(1, app(ResendQuota::class)->used($user, ResendQuota::MONTHLY));
        $this->assertSame('submitted', $c->fresh()->status);
    }

    public function test_scheduled_campaign_can_be_cancelled_through_resend(): void
    {
        $user = $this->resendUser();
        $c = $this->campaign($user);
        $this->contact($user, 'a@biz.example');
        $this->resendAccepts();
        $at = now()->addDay()->startOfHour();

        $this->approve($user, $c, ['send_mode' => 'schedule', 'scheduled_at' => $at->format('Y-m-d H:i')])->assertSessionHas('success');
        $this->assertSame('scheduled', $c->fresh()->status);
        $this->assertSame($at->toIso8601String(), $this->resendCalls('POST', '/broadcasts')[0]->data()['scheduled_at']);
        $this->assertSame('pending', OutboundEmail::sole()->status, 'Not sent until Resend sends it');

        $this->respondWith(self::API . '/broadcasts/bc_1', fn (HttpRequest $r) => Http::response(['object' => 'broadcast', 'id' => 'bc_1', 'deleted' => true]));
        $this->actingAs($user)->post("/user/campaigns/{$c->id}/cancel")->assertSessionHas('success');

        $this->assertSame('DELETE', $this->resendCalls('DELETE', '/broadcasts/bc_1')[0]->method());
        $this->assertSame('cancelled', $c->fresh()->status);
        $this->assertSame('cancelled', OutboundEmail::sole()->status);
        $this->assertSame(0, app(ResendQuota::class)->used($user, ResendQuota::MONTHLY));

        // Too early / too late schedules are refused
        $c2 = EmailCampaign::create($c->only(['owner_user_id', 'sender_id', 'name', 'subject', 'body']) + ['status' => 'draft']);
        $this->approve($user, $c2, ['send_mode' => 'schedule', 'scheduled_at' => now()->addMinutes(2)->format('Y-m-d H:i')])->assertSessionHas('error');
    }

    public function test_unclear_broadcast_outcome_is_never_re_created(): void
    {
        $user = $this->resendUser();
        $c = $this->campaign($user);
        $this->contact($user, 'a@biz.example');
        $this->resendAccepts(false, new ConnectionException('timeout'));

        $this->approve($user, $c)->assertSessionHas('success');
        $this->assertSame('unknown', $c->fresh()->status);

        app()->call([new \App\Jobs\SyncCampaignAudience($c->id), 'handle']);
        $this->assertCount(1, $this->resendCalls('POST', '/broadcasts'));
        $this->actingAs($user)->get("/user/campaigns/{$c->id}")->assertSee('will not create it again');
    }

    public function test_campaign_quota_is_enforced(): void
    {
        $user = $this->resendUser(['resend_emails_monthly' => '1']);
        $c = $this->campaign($user);
        $this->contact($user, 'a@biz.example');
        $this->contact($user, 'b@biz.example');

        $this->approve($user, $c)->assertSessionHas('error');
        $this->assertSame('draft', $c->fresh()->status);
        $this->assertCount(0, $this->resendCalls());
    }

    public function test_broadcast_webhooks_map_to_the_right_recipient(): void
    {
        $user = $this->resendUser();
        $c = $this->campaign($user);
        $this->contact($user, 'a@biz.example');
        $this->contact($user, 'b@biz.example');
        $this->resendAccepts();
        $this->approve($user, $c);
        [$config, $secret] = $this->webhookFor($c->fresh()->integration);

        $this->postWebhook($config, $secret, $this->event('email.delivered', ['email_id' => 're_b', 'broadcast_id' => 'bc_1', 'to' => ['B@biz.example']]))->assertOk();

        $b = OutboundEmail::where('to_email', 'b@biz.example')->sole();
        $this->assertSame('delivered', $b->delivery_status);
        $this->assertSame('re_b', $b->provider_message_id);
        $this->assertNull(OutboundEmail::where('to_email', 'a@biz.example')->sole()->delivery_status);
        $this->actingAs($user)->get("/user/campaigns/{$c->id}")->assertOk()->assertSee('Delivered');
    }

    public function test_unified_analytics_keep_provider_evidence_apart(): void
    {
        $user = $this->resendUser();
        $mk = fn (array $a) => OutboundEmail::create(array_merge([
            'owner_user_id' => $user->id, 'user_id' => $user->id, 'idempotency_key' => (string) Str::uuid(), 'from_email' => 'x@y.example',
            'to_email' => Str::random(5) . '@biz.example', 'subject' => 's', 'body' => 'b', 'status' => 'sent', 'queued_at' => now(), 'sent_at' => now(),
        ], $a));
        $g1 = $mk(['provider' => 'gmail', 'link_tracking' => true]);
        $mk(['provider' => 'gmail']);
        $r1 = $mk(['provider' => 'resend', 'delivered_at' => now(), 'delivery_status' => 'delivered']);
        $mk(['provider' => 'resend', 'bounced_at' => now(), 'delivery_status' => 'bounced']);
        $mk(['provider' => 'resend']);
        $mk(['provider' => 'resend', 'complained_at' => now(), 'delivered_at' => now(), 'delivery_status' => 'delivered']);
        $ev = fn ($email, $type, $cls = null) => EmailTrackingEvent::create(['owner_user_id' => $user->id, 'outbound_email_id' => $email->id, 'event_type' => $type, 'event_source' => 'x', 'classification' => $cls, 'occurred_at' => now()]);
        $ev($g1, EmailTrackingEvent::LINK_CLICK, EmailTrackingEvent::HUMAN);
        $ev($r1, EmailTrackingEvent::PROVIDER_CLICKED);
        $ev($r1, EmailTrackingEvent::PROVIDER_CLICKED);
        $ev($r1, EmailTrackingEvent::PROVIDER_OPENED);

        $f = ['from' => today()->subDays(29)->startOfDay(), 'to' => now()->endOfDay()];
        $all = app(EmailAnalytics::class)->summary($user, $f);
        $this->assertSame(6, $all['accepted']);
        $this->assertSame(4, $all['resend_accepted']);
        $this->assertSame(2, $all['delivered']);
        $this->assertSame(50.0, $all['delivery_rate'], 'Delivered / accepted by Resend, never over Gmail emails');
        $this->assertSame(1, $all['bounced']);
        $this->assertSame(1, $all['complained']);
        $this->assertSame(1, $all['link_requests'], 'App link requests (Gmail) only');
        $this->assertSame(2, $all['provider_clicks']);
        $this->assertSame(1, $all['provider_unique_clicks']);
        $this->assertSame(1, $all['provider_unique_opens']);

        $gmail = app(EmailAnalytics::class)->summary($user, $f + ['provider' => 'gmail']);
        $this->assertSame(2, $gmail['queued']);
        $this->assertNull($gmail['delivered'], 'Gmail delivery is not available, not zero');
        $this->assertNull($gmail['delivery_rate']);

        $this->actingAs($user)->get('/user/email-analytics?provider=gmail')->assertOk()->assertSee('Not available')->assertSee('Provider-reported delivery');
        $this->actingAs($user)->get('/user/email-analytics?provider=resend')->assertOk()->assertSee('Delivery: Delivered');
        $this->actingAs($user)->get('/user/email-analytics?provider=yahoo')->assertSessionHasErrors('provider');

        // Phase 5.1: the "accepted" card is named after the provider filter (it counts every provider in it)
        $this->actingAs($user)->get('/user/email-analytics?provider=resend')->assertSee('Accepted by Resend')
            ->assertSee('Check your Resend dashboard')->assertDontSee('Accepted by Gmail')->assertDontSee('Check Gmail &quot;Sent&quot;', false);
        $this->actingAs($user)->get('/user/email-analytics?provider=gmail')->assertSee('Accepted by Gmail')->assertDontSee('Accepted by Gmail / Resend');
        $this->actingAs($user)->get('/user/email-analytics')->assertSee('Accepted by Gmail / Resend')->assertSee('Results for emails sent through Gmail and Resend');
    }

    public function test_gmail_pipeline_ignores_resend_rows_and_quotas_are_separate(): void
    {
        $user = $this->resendUser(['email_sends_daily' => '1']);
        OutboundEmail::create([
            'owner_user_id' => $user->id, 'provider' => 'resend', 'user_id' => $user->id, 'idempotency_key' => (string) Str::uuid(),
            'from_email' => 'x@y.example', 'to_email' => 'r@biz.example', 'subject' => 's', 'body' => 'b', 'status' => 'pending', 'queued_at' => now(),
        ]);

        $this->assertSame(0, app(\App\Services\Gmail\OutboundEmailService::class)->sentToday($user), 'Resend does not use the Gmail daily limit');

        // The Gmail job never claims a Resend row
        $row = OutboundEmail::sole();
        $this->runJob($row->id);
        $this->assertSame('pending', $row->fresh()->status);
        $this->assertCount(0, $this->requestsTo(self::SEND_URL));
    }

    public function test_scheduler_uses_one_mail_worker_for_gmail_webhooks_and_resend(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $workers = $events->filter(fn ($e) => str_contains($e->command, 'queue:work'));

        $this->assertCount(2, $workers, 'Still only the mail worker and the AI worker');
        $this->assertNotNull($workers->first(fn ($e) => str_contains($e->command, '--queue=mail,webhook,resend')));
        $this->assertNotNull($events->first(fn ($e) => str_contains($e->command, 'resend:maintain')));
    }
}
