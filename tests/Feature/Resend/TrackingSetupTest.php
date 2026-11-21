<?php

namespace Tests\Feature\Resend;

use App\Models\EmailReplyRecord;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Models\ResendSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Resend tracking end to end: tracking subdomain + DNS step, HTML bodies (needed for
 * opens/clicks), replies through Resend receiving (email.received), the Email Analytics
 * "Tracking setup" banner, sender Reply-To and the per-email tracking panel.
 */
class TrackingSetupTest extends ResendTestCase
{
    private function resendEmail($user, array $attrs = []): OutboundEmail
    {
        return OutboundEmail::create(array_merge([
            'owner_user_id' => $user->id, 'provider' => 'resend', 'user_id' => $user->id,
            'saved_lead_id' => $this->leadFor($user)->id, 'idempotency_key' => (string) Str::uuid(),
            'from_email' => 'hello@mail.pixelstudio.example', 'to_email' => 'info@smiledental.example',
            'subject' => 'Hi', 'body' => 'Body', 'status' => 'sent', 'provider_message_id' => 're_1',
            'queued_at' => now(), 'sent_at' => now()->subHour(), 'delivered_at' => now(), 'delivery_status' => 'delivered',
        ], $attrs));
    }

    public function test_turning_tracking_on_adds_the_tracking_subdomain_and_shows_the_dns_step(): void
    {
        $user = $this->resendUser();
        [, $domain] = $this->connectResend($user);
        $this->resendEmail($user);

        $this->actingAs($user)->get(route('user.email-analytics.index'))->assertOk()
            ->assertSee('data-testid="tracking-setup"', false)
            ->assertSee('Opens and clicks are off for mail.pixelstudio.example')
            ->assertSee('Turn on open &amp; click tracking', false);

        // Resend: no tracking subdomain yet; after the update it asks for a CNAME record
        $after = $this->domainPayload('dom_1', 'mail.pixelstudio.example', 'verified') + ['open_tracking' => false, 'click_tracking' => false];
        $after['records'][] = ['record' => 'Tracking', 'name' => 'links', 'type' => 'CNAME', 'value' => 'links1.resend-dns.com', 'status' => 'not_started', 'ttl' => 'Auto'];
        $this->respondWith(self::API . '/domains/dom_1',
            fn () => Http::response($this->domainPayload('dom_1', 'mail.pixelstudio.example', 'verified')),
            fn () => Http::response(['object' => 'domain', 'id' => 'dom_1']),
            fn () => Http::response($after));

        $this->actingAs($user)->put(route('user.email-sending.resend.domains.tracking', $domain->id), ['open_tracking' => '1', 'click_tracking' => '1', 'confirm' => '1'])
            ->assertSessionHas('success');
        $this->assertSame(['open_tracking' => true, 'click_tracking' => true, 'tracking_subdomain' => 'links'], $this->resendCalls('PATCH')[0]->data());

        // The database keeps what Resend reports, and the banner shows the one DNS record left
        $this->assertFalse($domain->fresh()->open_tracking);
        $this->actingAs($user)->get(route('user.email-analytics.index'))
            ->assertSee('One DNS record left to finish open &amp; click tracking', false)
            ->assertSee('CNAME · links → links1.resend-dns.com')
            ->assertSee('Check again');
    }

    public function test_resend_emails_are_sent_with_an_html_version(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);
        $this->contact($user);
        $draft = $this->draftFor($user, ['body' => "Hi Sara,\n\nSee https://pixelstudio.example/work\n\nAyesha"]);
        $this->respondWith(self::API . '/emails', fn () => Http::response(['id' => 're_html']));
        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", [
            'recipient' => 'info@smiledental.example', 'sender_id' => $sender->id, 'confirm' => '1', 'confirm_consent' => '1',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('success');

        $payload = $this->resendCalls('POST', '/emails')[0]->data();
        $this->assertStringContainsString('Hi Sara,', $payload['text']);
        $this->assertStringContainsString('Hi Sara,<br>', $payload['html']);
        $this->assertStringContainsString('<a href="https://pixelstudio.example/work">', $payload['html']);
        $this->assertStringNotContainsString('<img', $payload['html'], 'Resend adds its own open pixel');
    }

    public function test_reply_received_through_resend_is_recorded(): void
    {
        $user = $this->resendUser();
        [$integration] = $this->connectResend($user);
        [$config, $secret] = $this->webhookFor($integration);
        $email = $this->resendEmail($user);

        $this->postWebhook($config, $secret, $this->event('email.received', [
            'email_id' => 'in_1', 'from' => 'Sara Khan <Info@SmileDental.example>', 'to' => ['replies@abc.resend.app'], 'subject' => 'Re: Hi',
        ]))->assertOk();

        $reply = EmailReplyRecord::sole();
        $this->assertSame($email->id, $reply->outbound_email_id);
        $this->assertSame('resend_inbound', $reply->source);
        $this->assertSame('replied', $reply->reply_status);
        $this->assertTrue(EmailTrackingEvent::where('event_type', EmailTrackingEvent::REPLY_DETECTED)->exists());

        // Mail from someone we never emailed is not a reply
        $this->postWebhook($config, $secret, $this->event('email.received', ['email_id' => 'in_2', 'from' => 'stranger@else.example', 'to' => ['replies@abc.resend.app']]))->assertOk();
        $this->assertSame(1, EmailReplyRecord::count());

        $this->actingAs($user)->get(route('user.email-analytics.email', $email->id))->assertSee('(detected automatically)');
    }

    public function test_email_page_explains_each_missing_status_and_banner_explains_replies(): void
    {
        $user = $this->resendUser();
        $this->connectResend($user);
        $email = $this->resendEmail($user);

        $this->actingAs($user)->get(route('user.email-analytics.email', $email->id))->assertOk()
            ->assertSee('data-testid="email-tracking"', false)
            ->assertSee('Open tracking is off for mail.pixelstudio.example in Resend');
        $this->actingAs($user)->get(route('user.email-analytics.index'))
            ->assertSee('Replies to Resend emails are not detected yet')
            ->assertSee('email.received');
    }

    public function test_sender_reply_to_can_be_changed_by_the_owner_only(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);

        $this->actingAs($user)->put(route('user.email-sending.resend.senders.update', $sender->id), ['reply_to' => 'Replies@ABC.resend.app'])
            ->assertSessionHas('success');
        $this->assertSame('replies@abc.resend.app', $sender->fresh()->reply_to);

        $this->actingAs($user)->put(route('user.email-sending.resend.senders.update', $sender->id), ['reply_to' => 'not-an-email'])
            ->assertSessionHasErrors('reply_to');

        $other = $this->resendUser();
        $this->actingAs($other)->put(route('user.email-sending.resend.senders.update', $sender->id), ['reply_to' => 'x@y.example'])
            ->assertNotFound();
        $this->assertSame('replies@abc.resend.app', ResendSender::find($sender->id)->reply_to);
    }
}
