<?php

namespace Tests\Feature\Resend;

use App\Models\EmailCampaign;
use App\Models\OutboundEmail;
use Illuminate\Support\Str;

/** Email Sending (Gmail tab) history: one-to-one emails only, provider shown. */
class EmailSendingPageTest extends ResendTestCase
{
    public function test_history_lists_one_to_one_emails_with_provider_and_hides_campaign_recipients(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);
        $mk = fn (array $a) => OutboundEmail::create(array_merge([
            'owner_user_id' => $user->id, 'user_id' => $user->id, 'idempotency_key' => (string) Str::uuid(),
            'from_email' => 'x@y.example', 'body' => 'b', 'queued_at' => now(),
        ], $a));
        $mk(['provider' => 'gmail', 'to_email' => 'gmail-one@biz.example', 'subject' => 'Gmail one', 'status' => 'sent', 'sent_at' => now()]);
        $mk(['provider' => 'resend', 'to_email' => 'resend-one@biz.example', 'subject' => 'Resend one', 'status' => 'sent', 'sent_at' => now()]);
        $campaign = EmailCampaign::create(['owner_user_id' => $user->id, 'created_by_user_id' => $user->id, 'sender_id' => $sender->id,
            'name' => 'N', 'subject' => 'S', 'body' => 'B', 'status' => 'scheduled', 'segment_filters' => []]);
        $mk(['provider' => 'resend', 'campaign_id' => $campaign->id, 'to_email' => 'campaign-person@biz.example', 'subject' => 'Campaign mail', 'status' => 'pending']);

        $this->actingAs($user)->get('/user/email-sending')->assertOk()
            ->assertSee('gmail-one@biz.example')->assertSee('Accepted by Gmail')
            ->assertSee('resend-one@biz.example')->assertSee('Accepted by Resend')
            ->assertDontSee('campaign-person@biz.example')
            ->assertSee('Campaign emails are on the')
            ->assertSee('class="cnm-ui ui-page ui-stack"', false);
    }
}
