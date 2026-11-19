<?php

namespace Tests\Feature\EmailSending;

use App\Models\AiEmailDraft;
use App\Models\AiLeadAnalysis;
use App\Models\ConnectedEmailAccount;
use App\Models\EmailAuditLog;
use App\Models\EmailSendAttempt;
use App\Models\EmailSuppression;
use App\Models\OutboundEmail;
use App\Models\UserSalesProfile;
use App\Services\Gmail\OutboundEmailService;
use Illuminate\Support\Facades\Queue;

class SendFlowTest extends GmailTestCase
{
    public function test_confirmed_send_goes_out_once_with_sender_identification_and_unsubscribe(): void
    {
        $user = $this->sender();
        $account = $this->connect($user);
        $draft = $this->draftFor($user);
        $this->gmailAccepts('msg-123', 'thr-456');

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())
            ->assertRedirect()->assertSessionHas('success');

        $email = OutboundEmail::sole();
        $this->assertSame('sent', $email->status);
        $this->assertSame('msg-123', $email->gmail_message_id);
        $this->assertSame('thr-456', $email->gmail_thread_id);
        $this->assertNotNull($email->rfc_message_id);
        $this->assertNotNull($email->sent_at);
        $this->assertSame($draft->id, $email->draft_id);
        $this->assertSame('ayesha@gmail.com', $email->from_email);
        $this->assertSame('info@smiledental.example', $email->to_email);
        $this->assertNotNull($account->fresh()->last_used_at);

        $sends = $this->requestsTo(self::SEND_URL);
        $this->assertCount(1, $sends);
        $this->assertSame('Bearer ya29.valid-access', $sends[0]->header('Authorization')[0]);

        $mime = $this->mimeOf($sends[0]);
        $this->assertStringContainsString("From: Ayesha <ayesha@gmail.com>\r\n", $mime);
        $this->assertStringContainsString("To: info@smiledental.example\r\n", $mime);
        $this->assertStringContainsString("Subject: A quick idea for your clinic\r\n", $mime);
        $this->assertMatchesRegularExpression('/List-Unsubscribe: <mailto:ayesha@gmail\.com\?subject=unsubscribe>, <http[^>]+email\/unsubscribe\/' . $email->id . '\?signature=[^>]+>/', $mime);
        $this->assertStringContainsString('List-Unsubscribe-Post: List-Unsubscribe=One-Click', $mime);
        $this->assertStringNotContainsString('Bcc:', $mime);
        $this->assertStringNotContainsString('Cc:', $mime);

        $body = $this->decodedBody($mime);
        $this->assertStringStartsWith("Hi there,\n\nI noticed your clinic", $body);
        $this->assertStringContainsString('Ayesha, Pixel Studio', $body);
        $this->assertStringContainsString('12 Mall Road, Lahore, Pakistan', $body);
        $this->assertStringContainsString('/email/unsubscribe/' . $email->id . '?signature=', $body);
        $this->assertSame($email->body, $body);

        $this->assertSame('success', EmailSendAttempt::sole()->result);
        $this->assertTrue(EmailAuditLog::where('action', 'send_queued')->exists());
        $this->assertTrue(EmailAuditLog::where('action', 'sent')->exists());

        $this->get('/user/email-sending')->assertSee('info@smiledental.example')->assertSee('Sent');
    }

    public function test_generating_or_approving_drafts_never_sends(): void
    {
        $user = $this->sender();
        $this->connect($user);
        $offer = $this->offerFor($user);
        $lead = $this->leadFor($user, ['email' => 'info@smiledental.example']);
        $this->fakeAi($this->happyAi($offer->id));

        $this->actingAs($user)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();
        $draft = AiEmailDraft::sole();
        $this->post("/user/ai-outreach/drafts/{$draft->id}/approve")->assertRedirect();

        $this->assertSame('approved', $draft->fresh()->review_status);
        $this->assertSame(0, OutboundEmail::count());
        $this->assertSame([], $this->requestsTo(self::SEND_URL));

        // The detail page offers a review step, not a send
        $analysis = AiLeadAnalysis::sole();
        $this->get("/user/ai-outreach/analyses/{$analysis->id}")->assertOk()
            ->assertSee('Review & send', false)
            ->assertSee('Review before sending')
            ->assertSee('I have reviewed this email');
    }

    public function test_unapproved_or_discarded_draft_cannot_be_sent(): void
    {
        $user = $this->sender();
        $this->connect($user);

        foreach (['draft', 'discarded'] as $status) {
            $draft = $this->draftFor($user, ['review_status' => $status]);
            $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())
                ->assertSessionHasErrors('send');
        }
        $failed = $this->draftFor($user, ['generation_status' => 'failed']);
        $this->post("/user/email-sending/drafts/{$failed->id}/send", $this->sendPayload())->assertSessionHasErrors('send');

        $this->assertSame(0, OutboundEmail::count());
        $this->assertSame([], $this->google);
    }

    public function test_draft_page_hides_send_for_unapproved_draft(): void
    {
        $user = $this->sender();
        $this->connect($user);
        $offer = $this->offerFor($user);
        $lead = $this->leadFor($user, ['email' => 'info@smiledental.example']);
        $this->fakeAi($this->happyAi($offer->id));
        $this->actingAs($user)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();

        $this->get('/user/ai-outreach/analyses/' . AiLeadAnalysis::sole()->id)->assertOk()
            ->assertDontSee('Review before sending');
    }

    public function test_explicit_confirmation_is_required(): void
    {
        $user = $this->sender();
        $this->connect($user);
        $draft = $this->draftFor($user);

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload(['confirm' => null]))
            ->assertSessionHasErrors('confirm');
        $this->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload(['idempotency_key' => 'short']))
            ->assertSessionHasErrors('idempotency_key');

        $this->assertSame(0, OutboundEmail::count());
    }

    public function test_missing_or_invalid_recipients_are_refused(): void
    {
        $user = $this->sender();
        $this->connect($user);
        $draft = $this->draftFor($user);

        foreach (['not-an-email', "a@b.com\r\nBcc: x@evil.com", 'a@b.com, c@d.com', 'a@localhost', '"x"@b.com'] as $bad) {
            $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload(['recipient' => $bad]))
                ->assertSessionHasErrors();
        }
        $this->assertSame(0, OutboundEmail::count());

        // A lead without an email shows the problem in the review step
        $noEmail = $this->draftFor($user, [], ['email' => null]);
        $preview = app(OutboundEmailService::class)->preview($user, $noEmail->load('lead'));
        $this->assertContains('This lead has no email address.', $preview['problems']);
        $this->assertSame([], $this->google);
    }

    public function test_suppressed_or_own_address_is_refused(): void
    {
        $user = $this->sender();
        $this->connect($user);
        $draft = $this->draftFor($user);
        EmailSuppression::suppress($user->id, 'INFO@smiledental.example', EmailSuppression::REASON_UNSUBSCRIBED);

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())
            ->assertSessionHasErrors(['send' => 'This address has unsubscribed or is blocked. It cannot be emailed.']);

        $this->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload(['recipient' => 'Ayesha@gmail.com']))
            ->assertSessionHasErrors('send');

        $this->assertSame(0, OutboundEmail::count());
    }

    public function test_requires_connected_gmail_and_sender_identification(): void
    {
        $user = $this->sender();
        $draft = $this->draftFor($user);

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())
            ->assertSessionHasErrors(['send' => 'Connect your Gmail account first.']);

        $this->connect($user, ['status' => 'needs_reconnect']);
        $this->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())->assertSessionHasErrors('send');

        ConnectedEmailAccount::where('user_id', $user->id)->update(['status' => 'active']);
        UserSalesProfile::where('owner_user_id', $user->id)->update(['sender_postal_address' => null]);
        $this->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())->assertSessionHasErrors('send');

        $this->assertSame(0, OutboundEmail::count());
        $this->assertSame([], $this->google);
    }

    public function test_double_submit_with_same_key_sends_once_and_draft_is_sent_at_most_once(): void
    {
        $user = $this->sender();
        $this->connect($user);
        $draft = $this->draftFor($user);
        $this->gmailAccepts();
        $payload = $this->sendPayload();

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $payload)->assertSessionHas('success');
        $this->post("/user/email-sending/drafts/{$draft->id}/send", $payload)->assertSessionHas('success');
        // New key (e.g. second tab) for an already-sent draft
        $this->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())
            ->assertSessionHasErrors(['send' => 'This draft has already been sent or is being sent.']);

        $this->assertSame(1, OutboundEmail::count());
        $this->assertCount(1, $this->requestsTo(self::SEND_URL));
    }

    public function test_job_never_sends_an_email_that_is_not_pending(): void
    {
        $user = $this->sender();
        $this->connect($user);
        $draft = $this->draftFor($user);
        $this->gmailAccepts();

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload());
        $email = OutboundEmail::sole();

        // A duplicate delivery of the same job (or a second worker) does nothing
        $this->runJob($email->id);
        $email->forceFill(['status' => 'cancelled'])->save();
        $this->runJob($email->id);

        $this->assertCount(1, $this->requestsTo(self::SEND_URL));
    }

    public function test_daily_limit_is_enforced(): void
    {
        $user = $this->sender(['email_sends_daily' => '1']);
        $this->connect($user);
        $this->gmailAccepts();

        $first = $this->draftFor($user);
        $this->actingAs($user)->post("/user/email-sending/drafts/{$first->id}/send", $this->sendPayload())->assertSessionHas('success');

        $second = $this->draftFor($user, [], ['email' => 'hello@other.example']);
        $this->post("/user/email-sending/drafts/{$second->id}/send", $this->sendPayload(['recipient' => 'hello@other.example']))
            ->assertSessionHasErrors(['send' => "You have reached today's sending limit (1)."]);

        $this->assertCount(1, $this->requestsTo(self::SEND_URL));

        // Tomorrow the allowance resets
        $this->travel(1)->days();
        $this->post("/user/email-sending/drafts/{$second->id}/send", $this->sendPayload(['recipient' => 'hello@other.example']))->assertSessionHas('success');
    }

    public function test_plan_without_sending_cannot_send(): void
    {
        $user = $this->sender(['email_sends_daily' => '0']);
        $this->connect($user);
        $draft = $this->draftFor($user);

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())->assertSessionHasErrors('send');

        $disabled = $this->sender(['gmail_sending_enabled' => 'false']);
        $this->connect($disabled, ['email' => 'other@gmail.com']);
        $d2 = $this->draftFor($disabled);
        $this->actingAs($disabled)->post("/user/email-sending/drafts/{$d2->id}/send", $this->sendPayload())->assertRedirect(route('user.subscription'));

        $this->assertSame(0, OutboundEmail::count());
    }

    public function test_other_accounts_drafts_and_emails_are_not_accessible(): void
    {
        Queue::fake();
        $a = $this->sender();
        $b = $this->sender();
        $this->connect($a);
        $this->connect($b, ['email' => 'b@gmail.com']);
        $draftA = $this->draftFor($a);

        $this->actingAs($b)->post("/user/email-sending/drafts/{$draftA->id}/send", $this->sendPayload())->assertNotFound();

        $this->actingAs($a)->post("/user/email-sending/drafts/{$draftA->id}/send", $this->sendPayload())->assertSessionHas('success');
        $email = OutboundEmail::sole();

        $this->actingAs($b)->post("/user/email-sending/emails/{$email->id}/cancel")->assertNotFound();
        $this->actingAs($b)->post("/user/email-sending/emails/{$email->id}/retry")->assertNotFound();
        $this->actingAs($b)->get('/user/email-sending')->assertDontSee('info@smiledental.example');
        $this->assertSame('pending', $email->fresh()->status);
    }

    public function test_team_member_sends_company_draft_from_own_gmail(): void
    {
        $company = $this->sender([], ['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);
        $this->connect($member, ['email' => 'member@gmail.com']);
        $draft = $this->draftFor($company);
        $this->gmailAccepts();

        $this->actingAs($member)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())->assertSessionHas('success');

        $email = OutboundEmail::sole();
        $this->assertSame($company->id, $email->owner_user_id);
        $this->assertSame($member->id, $email->user_id);
        $this->assertSame('member@gmail.com', $email->from_email);
        $this->assertSame('sent', $email->status);

        // The company owner sees it in history but has no Gmail of their own connected
        $this->actingAs($company)->get('/user/email-sending')->assertSee('info@smiledental.example')->assertSee('Connect Gmail');
    }

    public function test_cancel_pending_send(): void
    {
        Queue::fake();
        $user = $this->sender();
        $this->connect($user);
        $draft = $this->draftFor($user);

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload());
        $email = OutboundEmail::sole();
        Queue::assertPushedOn('mail', \App\Jobs\SendGmailMessage::class);

        $this->post("/user/email-sending/emails/{$email->id}/cancel")->assertSessionHas('success');
        $this->assertSame('cancelled', $email->fresh()->status);
        $this->assertNull($email->fresh()->draft_lock);

        // The queued job now does nothing
        $this->runJob($email->id);
        $this->assertSame([], $this->google);
        $this->assertSame('cancelled', $email->fresh()->status);

        $this->post("/user/email-sending/emails/{$email->id}/cancel")->assertSessionHas('error');
    }

    public function test_send_endpoint_is_rate_limited(): void
    {
        $user = $this->sender();
        $draft = $this->draftFor($user);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload(['confirm' => null]));
        }
        $this->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())->assertStatus(429);
    }

    public function test_extension_api_is_unchanged(): void
    {
        $this->postJson('/api/extension/login', [])->assertStatus(422);
    }
}
