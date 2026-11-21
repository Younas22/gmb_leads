<?php

namespace Tests\Feature\Resend;

use App\Models\OutboundEmail;
use App\Models\ResendSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Email Analytics explains why opens / clicks / replies are missing and fixes them:
 * Resend domain tracking (one click), sender Reply-To, and a per-email tracking panel.
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
            'queued_at' => now(), 'sent_at' => now(), 'delivered_at' => now(), 'delivery_status' => 'delivered',
        ], $attrs));
    }

    public function test_banner_lists_missing_tracking_and_turns_resend_tracking_on(): void
    {
        $user = $this->resendUser();
        [, $domain] = $this->connectResend($user);
        $this->resendEmail($user);

        $this->actingAs($user)->get(route('user.email-analytics.index'))->assertOk()
            ->assertSee('data-testid="tracking-setup"', false)
            ->assertSee('Opens and clicks are off for mail.pixelstudio.example')
            ->assertSee('Replies are not detected automatically')
            ->assertSee('Turn on open &amp; click tracking', false);

        // The banner's button
        $this->respondWith(self::API . '/domains/dom_1', fn () => Http::response(['object' => 'domain', 'id' => 'dom_1']));
        $this->actingAs($user)->put(route('user.email-sending.resend.domains.tracking', $domain->id), ['open_tracking' => '1', 'click_tracking' => '1', 'confirm' => '1'])
            ->assertSessionHas('success');
        $this->assertSame(['open_tracking' => true, 'click_tracking' => true], $this->resendCalls('PATCH')[0]->data());
        $this->actingAs($user)->get(route('user.email-analytics.index'))->assertDontSee('Opens and clicks are off for');
    }

    public function test_email_page_explains_each_missing_status(): void
    {
        $user = $this->resendUser();
        $this->connectResend($user);
        $email = $this->resendEmail($user);

        $this->actingAs($user)->get(route('user.email-analytics.email', $email->id))->assertOk()
            ->assertSee('data-testid="email-tracking"', false)
            ->assertSee('Open tracking is off for mail.pixelstudio.example in Resend')
            ->assertSee('Replies are not read automatically for this account');
    }

    public function test_sender_reply_to_can_be_changed_by_the_owner_only(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);

        $this->actingAs($user)->put(route('user.email-sending.resend.senders.update', $sender->id), ['reply_to' => 'Ayesha@Gmail.com'])
            ->assertSessionHas('success');
        $this->assertSame('ayesha@gmail.com', $sender->fresh()->reply_to);

        $this->actingAs($user)->put(route('user.email-sending.resend.senders.update', $sender->id), ['reply_to' => 'not-an-email'])
            ->assertSessionHasErrors('reply_to');

        $other = $this->resendUser();
        $this->actingAs($other)->put(route('user.email-sending.resend.senders.update', $sender->id), ['reply_to' => 'x@y.example'])
            ->assertNotFound();
        $this->assertSame('ayesha@gmail.com', ResendSender::find($sender->id)->reply_to);
    }
}
