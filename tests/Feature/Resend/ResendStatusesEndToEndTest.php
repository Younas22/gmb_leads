<?php

namespace Tests\Feature\Resend;

use App\Models\EmailFollowUp;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Services\EmailTracking\EmailAnalytics;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Whole Resend path, as on /user/email-analytics: send → automatic follow-up reminder →
 * webhooks (delivered, opened, clicked, bounced, complained, unsubscribed) → reply →
 * numbers on the dashboard and the email page.
 */
class ResendStatusesEndToEndTest extends ResendTestCase
{
    private function sendTo(\App\Models\User $user, int $senderId, string $to, string $msgId): OutboundEmail
    {
        $this->contact($user, $to);
        $draft = $this->draftFor($user);
        $this->respondWith(self::API . '/emails', fn () => Http::response(['id' => $msgId]));
        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", [
            'recipient' => $to, 'sender_id' => $senderId, 'confirm' => '1', 'confirm_consent' => '1',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('success');

        return OutboundEmail::where('to_email', $to)->sole();
    }

    public function test_every_resend_status_reaches_email_analytics(): void
    {
        $user = $this->resendUser();
        [$integration, , $sender] = $this->connectResend($user);
        [$config, $secret] = $this->webhookFor($integration);

        $a = $this->sendTo($user, $sender->id, 'a@clinic.example', 're_a');
        $b = $this->sendTo($user, $sender->id, 'b@clinic.example', 're_b');
        $c = $this->sendTo($user, $sender->id, 'c@clinic.example', 're_c');
        $d = $this->sendTo($user, $sender->id, 'd@clinic.example', 're_d');
        $this->assertSame('sent', $a->status);

        // Follow-up date is set automatically (3 days after sending)
        $auto = EmailFollowUp::where('outbound_email_id', $a->id)->sole();
        $this->assertSame(today()->addDays(3)->toDateString(), $auto->due_date->toDateString());
        $this->assertStringContainsString('Auto reminder', $auto->notes);

        foreach (['a', 'b', 'c', 'd'] as $x) {
            $this->postWebhook($config, $secret, $this->event('email.delivered', ['email_id' => "re_{$x}", 'to' => ["{$x}@clinic.example"]]))->assertOk();
        }
        $this->postWebhook($config, $secret, $this->event('email.opened', ['email_id' => 're_a']))->assertOk();
        $this->postWebhook($config, $secret, $this->event('email.opened', ['email_id' => 're_a']))->assertOk();
        $this->postWebhook($config, $secret, $this->event('email.clicked', ['email_id' => 're_a', 'click' => ['link' => 'https://pixelstudio.example/work']]))->assertOk();
        $this->postWebhook($config, $secret, $this->event('email.bounced', ['email_id' => 're_b', 'bounce' => ['type' => 'Permanent']]))->assertOk();
        $this->postWebhook($config, $secret, $this->event('email.complained', ['email_id' => 're_c']))->assertOk();
        $this->postWebhook($config, $secret, ['type' => 'contact.updated', 'created_at' => now()->toIso8601String(),
            'data' => ['id' => 'c_d', 'email' => 'D@Clinic.example', 'unsubscribed' => true]])->assertOk();

        // Reply (recorded on the email page)
        $this->actingAs($user)->post(route('user.email-analytics.reply.store', $a->id), [
            'reply_status' => 'interested', 'reply_received_at' => now()->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors();

        $s = app(EmailAnalytics::class)->summary($user, ['from' => now()->subDays(7), 'to' => now()]);
        $this->assertSame(4, $s['accepted']);
        $this->assertSame(4, $s['delivered']);
        $this->assertSame(1, $s['bounced']);
        $this->assertSame(1, $s['complained']);
        $this->assertSame(2, $s['provider_opens']);
        $this->assertSame(1, $s['provider_unique_opens']);
        $this->assertSame(1, $s['provider_clicks']);
        $this->assertSame(1, $s['replies']);
        $this->assertSame(1, $s['unsubscribes'], 'Resend unsubscribe is counted');

        // Bounce / complaint / unsubscribe block the address and stop its follow-ups
        foreach (['b', 'c', 'd'] as $x) {
            $this->assertTrue(EmailSuppression::isSuppressed($user->id, "{$x}@clinic.example"), $x);
            $this->assertSame(0, EmailFollowUp::where('outbound_email_id', ${$x}->id)->open()->count(), $x);
        }
        $this->assertSame(1, EmailFollowUp::where('outbound_email_id', $a->id)->open()->count());

        $this->actingAs($user)->get(route('user.email-analytics.index'))->assertOk();
        $this->actingAs($user)->get(route('user.email-analytics.email', $a->id))->assertOk()
            ->assertSee('Auto reminder', false);
        $this->assertTrue(EmailTrackingEvent::where('event_type', EmailTrackingEvent::OPTED_OUT)->where('outbound_email_id', $d->id)->exists());
    }

    public function test_auto_follow_up_is_not_duplicated_and_a_due_one_is_closed_by_the_next_email(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);

        $first = $this->sendTo($user, $sender->id, 'a@clinic.example', 're_1');
        $reminder = EmailFollowUp::where('outbound_email_id', $first->id)->sole();

        // Reminder is due → the next email to the same contact is the follow-up
        $reminder->forceFill(['due_date' => today()->toDateString()])->save();
        $draft = $this->draftFor($user);
        $this->respondWith(self::API . '/emails', fn () => Http::response(['id' => 're_2']));
        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", [
            'recipient' => 'a@clinic.example', 'sender_id' => $sender->id, 'confirm' => '1', 'confirm_consent' => '1',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('success');

        $this->assertSame('completed', $reminder->fresh()->status);
        $this->assertSame(1, EmailFollowUp::open()->count(), 'one new reminder, not two');

        // Off switch
        config(['email_tracking.auto_follow_up_days' => 0]);
        $this->sendTo($user, $sender->id, 'z@clinic.example', 're_z');
        $this->assertSame(1, EmailFollowUp::open()->count());
    }
}
