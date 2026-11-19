<?php

namespace Tests\Feature\Resend;

use App\Models\EmailCampaign;

/**
 * Phase 5.1 (browser testing): validation errors for fields that had no inline message
 * were silently dropped, so a failed form looked like nothing happened.
 */
class FormFeedbackTest extends ResendTestCase
{
    public function test_sender_field_errors_are_shown(): void
    {
        $user = $this->resendUser();
        [, $domain] = $this->connectResend($user);

        $this->actingAs($user)->from('/user/email-sending/resend')
            ->post('/user/email-sending/resend/senders', ['domain_id' => $domain->id, 'from_name' => '', 'local_part' => ''])
            ->assertRedirect('/user/email-sending/resend');

        $this->actingAs($user)->get('/user/email-sending/resend')->assertOk()
            ->assertSee('data-testid="form-errors"', false)->assertSee('The from name field is required.');
        $this->assertCount(0, $this->resendCalls());
    }

    public function test_invalid_webhook_secret_error_is_visible(): void
    {
        $user = $this->resendUser();
        $this->connectResend($user);

        $this->actingAs($user)->from('/user/email-sending/resend')
            ->put('/user/email-sending/resend/webhook/secret', ['signing_secret' => 'nope']);

        $this->actingAs($user)->get('/user/email-sending/resend')
            ->assertSee('starts with &quot;whsec_&quot;', false)
            ->assertSee('<details class="mt-3 text-xs text-gray-600"  open >', false);
    }

    public function test_consent_field_errors_are_shown_on_the_contacts_page(): void
    {
        $user = $this->resendUser();

        $this->actingAs($user)->from('/user/campaigns/contacts')
            ->post('/user/campaigns/contacts', ['email' => 'pat@ok.example', 'consent_evidence' => 'short']);

        $this->actingAs($user)->get('/user/campaigns/contacts')
            ->assertSee('The consent source field is required.')
            ->assertSee('Describe where and how this person opted in (at least 10 characters).');
    }

    public function test_campaign_approval_confirmation_errors_are_shown(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);
        $this->contact($user, 'a@biz.example');
        $c = EmailCampaign::create(['owner_user_id' => $user->id, 'created_by_user_id' => $user->id, 'sender_id' => $sender->id,
            'name' => 'N', 'subject' => 'S', 'body' => 'B', 'status' => 'draft', 'segment_filters' => []]);

        $this->actingAs($user)->from("/user/campaigns/{$c->id}")->post("/user/campaigns/{$c->id}/approve", ['send_mode' => 'now', 'expected_count' => 1]);

        $this->actingAs($user)->get("/user/campaigns/{$c->id}")->assertSee('Confirm that every recipient opted in to receive these emails.');
        $this->assertSame('draft', $c->fresh()->status);
        $this->assertCount(0, $this->resendCalls());
    }
}
