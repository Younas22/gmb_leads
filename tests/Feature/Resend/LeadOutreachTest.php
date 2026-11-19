<?php

namespace Tests\Feature\Resend;

use App\Models\EmailAuditLog;
use App\Models\EmailCampaign;
use App\Models\EmailSuppression;
use App\Models\MarketingContact;
use App\Models\OutboundEmail;
use App\Models\ResendIntegration;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Optional owner setting: one-to-one Resend emails to saved leads (no consent record).
 * Off by default; campaigns are never affected; the do-not-email list always wins.
 */
class LeadOutreachTest extends ResendTestCase
{
    private function setup_(): array
    {
        $user = $this->resendUser();
        [$integration, , $sender] = $this->connectResend($user);
        $draft = $this->draftFor($user); // lead email: info@smiledental.example, not a contact
        $this->respondWith(self::API . '/emails', fn () => Http::response(['id' => 're_lead_1']));

        return [$user, $integration, $sender, $draft];
    }

    private function allow(User $user): void
    {
        $this->actingAs($user)->put('/user/email-sending/resend/lead-outreach', ['enabled' => '1', 'acknowledge' => '1'])->assertSessionHas('success');
    }

    private function send(User $user, $draft, int $senderId, array $extra = [])
    {
        return $this->actingAs($user)->post("/user/email-sending/resend/drafts/{$draft->id}/send", array_merge([
            'recipient' => 'info@smiledental.example', 'sender_id' => $senderId, 'confirm' => '1', 'confirm_lead_outreach' => '1',
            'idempotency_key' => (string) Str::uuid(),
        ], $extra));
    }

    public function test_off_by_default_and_needs_the_risk_acknowledgement(): void
    {
        [$user, $integration, $sender, $draft] = $this->setup_();
        $this->assertFalse($integration->fresh()->allowsLeadOutreach());
        $this->send($user, $draft, $sender->id)->assertSessionHasErrors('confirm_consent');
        $this->assertSame(0, OutboundEmail::count());

        $this->actingAs($user)->put('/user/email-sending/resend/lead-outreach', ['enabled' => '1'])->assertSessionHasErrors('acknowledge');
        $this->assertFalse($integration->fresh()->allowsLeadOutreach());

        $this->actingAs($user)->get('/user/email-sending/resend')->assertOk()->assertSee('One-to-one Resend emails to saved leads')->assertSee('Acceptable Use Policy');
    }

    public function test_when_allowed_the_lead_is_emailed_once_with_compliance_footer_and_no_fake_consent(): void
    {
        [$user, $integration, $sender, $draft] = $this->setup_();
        $this->allow($user);
        $this->assertTrue($integration->fresh()->allowsLeadOutreach());
        $this->assertTrue(EmailAuditLog::where('action', 'resend_lead_outreach_enabled')->exists());

        $page = $this->actingAs($user)->get("/user/ai-outreach/analyses/" . $this->analysisFor($draft))->assertOk();
        $page->assertSee('To (saved lead, not an opted-in contact)')->assertSee('name="confirm_lead_outreach"', false)->assertDontSee('name="confirm_consent"', false);

        $this->send($user, $draft, $sender->id, ['confirm_lead_outreach' => null])->assertSessionHasErrors('confirm_lead_outreach');
        $this->send($user, $draft, $sender->id)->assertSessionHas('success');

        $email = OutboundEmail::sole();
        $this->assertSame(['resend', 'sent', null], [$email->provider, $email->status, $email->marketing_contact_id]);
        $data = $this->resendCalls('POST', '/emails')[0]->data();
        $this->assertSame(['info@smiledental.example'], $data['to']);
        $this->assertStringContainsString('12 Mall Road', $data['text'], 'Postal address');
        $this->assertStringStartsWith('<http', $data['headers']['List-Unsubscribe']);
        $this->assertSame(0, MarketingContact::count(), 'No consent record is invented');
    }

    public function test_only_the_leads_own_address_and_never_a_suppressed_one(): void
    {
        [$user, , $sender, $draft] = $this->setup_();
        $this->allow($user);

        $this->send($user, $draft, $sender->id, ['recipient' => 'someone-else@biz.example'])->assertSessionHasErrors('confirm_consent');
        EmailSuppression::suppress($user->id, 'info@smiledental.example', EmailSuppression::REASON_UNSUBSCRIBED);
        $this->send($user, $draft, $sender->id)->assertSessionHasErrors('resend_send');

        $this->assertSame(0, OutboundEmail::count());
        $this->assertCount(0, $this->resendCalls('POST', '/emails'));
    }

    public function test_switching_off_before_the_job_runs_stops_the_email(): void
    {
        [$user, $integration, $sender, $draft] = $this->setup_();
        $this->allow($user);
        Queue::fake();
        $this->send($user, $draft, $sender->id)->assertSessionHas('success');
        $this->actingAs($user)->put('/user/email-sending/resend/lead-outreach', ['enabled' => '0'])->assertSessionHas('success');
        $this->assertFalse($integration->fresh()->allowsLeadOutreach());

        $email = OutboundEmail::sole();
        app()->call([new \App\Jobs\SendResendEmail($email->id), 'handle']);

        $this->assertSame(['failed', 'no_consent'], [$email->fresh()->status, $email->fresh()->error_code]);
        $this->assertCount(0, $this->resendCalls('POST', '/emails'));
    }

    public function test_campaigns_still_reach_opted_in_contacts_only(): void
    {
        [$user, , $sender] = $this->setup_();
        $this->allow($user);
        $this->leadFor($user, ['email' => 'lead-only@biz.example']);
        $this->contact($user, 'optedin@biz.example');
        $c = EmailCampaign::create(['owner_user_id' => $user->id, 'created_by_user_id' => $user->id, 'sender_id' => $sender->id,
            'name' => 'N', 'subject' => 'S', 'body' => 'B', 'status' => 'draft', 'segment_filters' => []]);

        $this->assertSame(['optedin@biz.example'], app(\App\Services\Resend\CampaignService::class)->eligible($c)->pluck('email')->all());
    }

    public function test_team_members_cannot_change_the_setting(): void
    {
        [$owner] = $this->setup_();
        $member = $this->makeUser(['company_id' => $owner->id]);
        $this->actingAs($member)->put('/user/email-sending/resend/lead-outreach', ['enabled' => '1', 'acknowledge' => '1'])->assertForbidden();
        $this->assertFalse(ResendIntegration::where('owner_user_id', $owner->id)->sole()->allowsLeadOutreach());
    }

    private function analysisFor($draft): int
    {
        $a = new \App\Models\AiLeadAnalysis();
        $a->forceFill(['owner_user_id' => $draft->owner_user_id, 'saved_lead_id' => $draft->saved_lead_id, 'status' => 'completed'])->save();

        return $a->id;
    }
}
