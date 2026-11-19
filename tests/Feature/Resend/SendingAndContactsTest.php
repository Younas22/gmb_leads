<?php

namespace Tests\Feature\Resend;

use App\Models\EmailReplyRecord;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\MarketingContact;
use App\Models\OutboundEmail;
use App\Services\Resend\ResendQuota;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/** Phase 5: Resend single sends (consent-gated), contacts and imports. */
class SendingAndContactsTest extends ResendTestCase
{
    private function payload(int $senderId, array $overrides = []): array
    {
        return array_merge([
            'recipient' => 'info@smiledental.example', 'sender_id' => $senderId, 'confirm' => '1', 'confirm_consent' => '1',
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ], $overrides);
    }

    private function accepts(string $id = 're_msg_1'): void
    {
        $this->respondWith(self::API . '/emails', fn () => Http::response(['id' => $id]));
    }

    public function test_a_scraped_lead_without_consent_cannot_be_emailed_through_resend(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);
        $draft = $this->draftFor($user);

        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", $this->payload($sender->id))
            ->assertSessionHasErrors('resend_send');

        $this->assertStringContainsString('not an opted-in contact', session('errors')->first('resend_send'));
        $this->assertSame(0, OutboundEmail::count());
        $this->assertCount(0, $this->resendCalls());
        $this->assertSame(0, MarketingContact::count(), 'Leads never become contacts');
    }

    public function test_opted_in_contact_is_sent_with_idempotency_and_compliant_headers(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);
        $this->contact($user);
        $draft = $this->draftFor($user, ['body' => "Hi Sara,\n\nSee https://pixelstudio.example/work\n\nAyesha"]);
        $this->accepts();

        $payload = $this->payload($sender->id);
        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", $payload)->assertSessionHas('success');
        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", $payload); // double submit

        $email = OutboundEmail::sole();
        $this->assertSame('resend', $email->provider);
        $this->assertSame('sent', $email->status);
        $this->assertSame('re_msg_1', $email->provider_message_id);
        $this->assertNull($email->delivery_status, 'Acceptance is not delivery');
        $this->assertFalse($email->link_tracking);

        $calls = $this->resendCalls('POST', '/emails');
        $this->assertCount(1, $calls);
        $req = $calls[0];
        $this->assertSame('cnm-' . $email->idempotency_key, $req->header('Idempotency-Key')[0]);
        $data = $req->data();
        $this->assertSame('"Ayesha" <hello@mail.pixelstudio.example>', $data['from']);
        $this->assertSame(['info@smiledental.example'], $data['to']);
        $this->assertStringContainsString('https://pixelstudio.example/work', $data['text']);
        $this->assertStringNotContainsString('/r/', $data['text'], 'No app redirect wrapping for Resend');
        $this->assertStringStartsWith('<http', $data['headers']['List-Unsubscribe']);
        $this->assertSame('List-Unsubscribe=One-Click', $data['headers']['List-Unsubscribe-Post']);

        $this->assertSame(1, app(ResendQuota::class)->used($user, ResendQuota::DAILY));
        $this->assertSame('Email accepted by Resend', EmailTrackingEvent::where('event_type', 'accepted')->sole()->typeLabel());

        // The draft is locked for Gmail too
        $this->assertContains('This draft has already been sent or is being sent.', app(\App\Services\Gmail\OutboundEmailService::class)->preview($user, $draft->fresh())['problems']);
    }

    public function test_send_time_checks_and_known_failures_release_quota(): void
    {
        $user = $this->resendUser();
        [, $domain, $sender] = $this->connectResend($user);
        $this->contact($user);
        $draft = $this->draftFor($user);
        $this->respond(self::API . '/emails', [['statusCode' => 403, 'name' => 'validation_error', 'message' => 'The mail.pixelstudio.example domain is not verified.'], 403]);

        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", $this->payload($sender->id));

        $email = OutboundEmail::sole();
        $this->assertSame('failed', $email->status);
        $this->assertFalse($email->outcome_unknown);
        $this->assertStringContainsString('not verified', $email->error_message);
        $this->assertNull($email->draft_lock, 'Known failure frees the draft');
        $this->assertSame(0, app(ResendQuota::class)->used($user, ResendQuota::DAILY));
    }

    public function test_ambiguous_outcome_is_never_retried_automatically(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);
        $this->contact($user);
        $draft = $this->draftFor($user);
        $this->respondWith(self::API . '/emails', new ConnectionException('timeout'));

        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", $this->payload($sender->id));

        $email = OutboundEmail::sole();
        $this->assertTrue($email->isUnknown());
        $this->assertSame((string) $draft->id, $email->draft_lock);
        $this->assertCount(1, $this->resendCalls('POST', '/emails'));

        $this->actingAs($user)->post("/user/email-sending/emails/{$email->id}/retry")->assertSessionHas('error');
        $this->accepts('re_msg_retry');
        $this->actingAs($user)->post("/user/email-sending/emails/{$email->id}/retry", ['confirm_not_sent' => '1'])->assertSessionHas('success');
        $this->assertSame('sent', $email->fresh()->status);
        // Same Idempotency-Key reused: Resend will not send twice within 24 hours
        $keys = array_map(fn ($r) => $r->header('Idempotency-Key')[0], $this->resendCalls('POST', '/emails'));
        $this->assertSame($keys[0], $keys[1]);
    }

    public function test_suppressed_unverified_and_over_quota_sends_are_blocked(): void
    {
        $user = $this->resendUser(['resend_emails_daily' => '1']);
        [, $domain, $sender] = $this->connectResend($user);
        $this->contact($user);
        $this->contact($user, 'b@clinic.example');
        $draft = $this->draftFor($user);

        EmailSuppression::suppress($user->id, 'info@smiledental.example', 'manual');
        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", $this->payload($sender->id))->assertSessionHasErrors('resend_send');
        EmailSuppression::query()->delete();

        $domain->update(['status' => 'pending']);
        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", $this->payload($sender->id))->assertSessionHasErrors('resend_send');
        $domain->update(['status' => 'verified']);

        $this->accepts();
        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", $this->payload($sender->id))->assertSessionHas('success');
        $draft2 = $this->draftFor($user, [], [], $this->leadFor($user, ['email' => 'b@clinic.example']));
        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft2->id}/send", $this->payload($sender->id, ['recipient' => 'b@clinic.example']))->assertSessionHasErrors('resend_send');
        $this->assertStringContainsString('plan limit', session('errors')->first('resend_send'));
        $this->assertSame(1, OutboundEmail::count());
    }

    public function test_review_screen_offers_resend_only_with_consent(): void
    {
        $user = $this->resendUser();
        $this->connectResend($user);
        $offer = $this->offerFor($user);
        $lead = $this->leadFor($user, ['email' => 'info@smiledental.example']);
        $this->fakeAi($this->happyAi($offer->id));
        $this->actingAs($user)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();
        $draft = \App\Models\AiEmailDraft::sole();
        $this->post("/user/ai-outreach/drafts/{$draft->id}/approve");
        $analysis = \App\Models\AiLeadAnalysis::sole();

        $this->get("/user/ai-outreach/analyses/{$analysis->id}")->assertOk()->assertSee('Send via Resend')
            ->assertSee('not an opted-in contact')->assertDontSee('Review & send via Resend', false);

        $this->contact($user);
        $this->get("/user/ai-outreach/analyses/{$analysis->id}")->assertOk()->assertSee('Review & send via Resend', false)
            ->assertSee('CustomerNearMe link tracking is never added to Resend emails');
    }

    public function test_manual_contacts_require_consent_evidence_and_blocked_addresses_stay_blocked(): void
    {
        $user = $this->resendUser();
        $base = ['email' => 'a@biz.example', 'consent_source' => 'signup_form', 'consent_date' => today()->subDay()->toDateString(), 'consent_evidence' => 'Signed up on our website form'];

        $this->actingAs($user)->post('/user/campaigns/contacts', array_merge($base, ['consent_evidence' => 'yes']))->assertSessionHasErrors('consent_evidence');
        $this->actingAs($user)->post('/user/campaigns/contacts', array_merge($base, ['consent_source' => 'bought_list']))->assertSessionHasErrors('consent_source');
        $this->actingAs($user)->post('/user/campaigns/contacts', array_merge($base, ['consent_date' => today()->addDay()->toDateString()]))->assertSessionHasErrors('consent_date');
        $this->actingAs($user)->post('/user/campaigns/contacts', $base)->assertSessionHas('success');
        $contact = MarketingContact::sole();
        $this->assertTrue($contact->isSendable());
        $this->assertDatabaseHas('marketing_consent_logs', ['contact_id' => $contact->id, 'action' => 'consent_recorded', 'user_id' => $user->id]);

        $this->actingAs($user)->post("/user/campaigns/contacts/{$contact->id}/withdraw")->assertSessionHas('success');
        $this->assertTrue(EmailSuppression::isSuppressed($user->id, 'a@biz.example'));
        $this->actingAs($user)->post('/user/campaigns/contacts', $base)->assertSessionHasErrors('email');
        $this->assertFalse($contact->fresh()->isSendable());
    }

    public function test_csv_import_rejects_missing_consent_and_never_resubscribes(): void
    {
        $user = $this->resendUser();
        $this->contact($user, 'gone@biz.example', ['unsubscribed_at' => now()]);
        EmailSuppression::suppress($user->id, 'bounced@biz.example', 'bounced');
        $d = today()->subMonth()->toDateString();
        $csv = "email,first_name,consent_source,consent_date,consent_evidence,tags\n"
            . "ok1@biz.example,Ali,signup_form,{$d},Newsletter form on website,vip;dental\n"
            . "OK1@biz.example,Ali,signup_form,{$d},Duplicate row in file,\n"
            . "noevidence@biz.example,Bo,signup_form,{$d},,\n"
            . "badsource@biz.example,Bo,scraped,{$d},Found on maps,\n"
            . "gone@biz.example,Gone,signup_form,{$d},Signed up again they say,\n"
            . "bounced@biz.example,B,signup_form,{$d},Signed up at event booth,\n"
            . "not-an-email,X,signup_form,{$d},Evidence text here,\n";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

        $this->actingAs($user)->post('/user/campaigns/contacts/import', ['file' => $file])->assertSessionHasErrors('confirm');
        $this->actingAs($user)->post('/user/campaigns/contacts/import', ['file' => $file, 'confirm' => '1'])->assertSessionHas('success');

        $report = session('import_report');
        $this->assertSame(1, $report['created']);
        $this->assertSame(2, $report['blocked']);
        $this->assertSame(4, $report['rejected']);
        $this->assertSame(['vip', 'dental'], MarketingContact::where('email', 'ok1@biz.example')->sole()->tags);
        $this->assertNotNull(MarketingContact::where('email', 'gone@biz.example')->sole()->unsubscribed_at);
        $this->assertNull(MarketingContact::where('email', 'bounced@biz.example')->first());
    }

    public function test_contact_limit_and_tenant_isolation(): void
    {
        $user = $this->resendUser(['resend_contacts_limit' => '1']);
        $base = ['consent_source' => 'signup_form', 'consent_date' => today()->toDateString(), 'consent_evidence' => 'Signed up on our website form'];
        $this->actingAs($user)->post('/user/campaigns/contacts', $base + ['email' => 'one@biz.example'])->assertSessionHas('success');
        $this->actingAs($user)->post('/user/campaigns/contacts', $base + ['email' => 'two@biz.example'])->assertSessionHasErrors('email');

        $other = $this->resendUser();
        $contact = MarketingContact::sole();
        $this->actingAs($other)->post("/user/campaigns/contacts/{$contact->id}/withdraw")->assertNotFound();
        $this->actingAs($other)->get('/user/campaigns/contacts')->assertOk()->assertDontSee('one@biz.example');
        $this->assertTrue($contact->fresh()->isSendable());
    }

    public function test_correcting_not_interested_never_removes_an_independent_unsubscribe(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);
        $this->contact($user);
        $draft = $this->draftFor($user);
        $this->accepts();
        $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", $this->payload($sender->id));
        $email = OutboundEmail::sole();

        $reply = fn (string $s) => $this->actingAs($user)->post("/user/email-analytics/emails/{$email->id}/reply", ['reply_status' => $s, 'reply_received_at' => now()->toDateTimeString()]);
        $reply('not_interested')->assertSessionHas('success');
        $this->assertSame('not_interested', EmailSuppression::sole()->reason);

        // The recipient then unsubscribes through the email's link
        auth()->logout();
        $this->post(app(\App\Services\Gmail\OutboundEmailService::class)->unsubscribeUrl($email))->assertOk();
        $this->assertSame('unsubscribed', EmailSuppression::sole()->reason);

        // A later correction of the reply must not lift the real opt-out
        $reply('interested')->assertSessionHas('success');
        $this->assertTrue(EmailSuppression::isSuppressed($user->id, $email->to_email));
        $this->assertSame('interested', EmailReplyRecord::sole()->reply_status);
        $this->assertNotNull(MarketingContact::sole()->unsubscribed_at);
    }

    public function test_follow_up_for_a_contact_that_is_not_a_saved_lead(): void
    {
        $user = $this->resendUser();
        $contact = $this->contact($user, 'client@biz.example');

        $this->actingAs($user)->post('/user/email-follow-ups', ['marketing_contact_id' => $contact->id, 'due_date' => today()->addDays(2)->toDateString(), 'notes' => 'Check in'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('email_follow_ups', ['marketing_contact_id' => $contact->id, 'saved_lead_id' => null, 'status' => 'open']);
        $this->actingAs($user)->get('/user/email-follow-ups')->assertOk()->assertSee('Sara')->assertSee('(contact)', false);

        EmailSuppression::suppress($user->id, 'client@biz.example', 'complaint');
        $this->actingAs($user)->post('/user/email-follow-ups', ['marketing_contact_id' => $contact->id, 'due_date' => today()->addDays(3)->toDateString()])
            ->assertSessionHasErrors('follow_up');
    }
}
