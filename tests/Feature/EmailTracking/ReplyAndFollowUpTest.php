<?php

namespace Tests\Feature\EmailTracking;

use App\Models\EmailFollowUp;
use App\Models\EmailReplyRecord;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\LeadCenterLead;
use App\Models\OutboundEmail;
use App\Models\SavedLead;
use App\Models\User;

/** Phase 4: manual replies, follow-up reminders and lead status sync. */
class ReplyAndFollowUpTest extends TrackingTestCase
{
    private function sentEmail(?User $user = null): array
    {
        $user ??= $this->trackingSender();
        $this->connect($user);
        $draft = $this->draftFor($user);
        $email = $this->sendDraft($user, $draft);

        return [$user, $email, SavedLead::find($email->saved_lead_id)];
    }

    private function centerLead(SavedLead $lead, string $status = 'pending'): LeadCenterLead
    {
        return LeadCenterLead::create([
            'user_id' => $lead->user_id, 'saved_lead_id' => $lead->id, 'company_name' => $lead->name,
            'status' => $status, 'dedupe_key' => 'k-' . $lead->id,
        ]);
    }

    public function test_accepted_email_marks_the_lead_contacted_and_logs_events(): void
    {
        $user = $this->trackingSender();
        $this->connect($user);
        $draft = $this->draftFor($user);
        $center = $this->centerLead(SavedLead::find($draft->saved_lead_id));

        $email = $this->sendDraft($user, $draft);

        $this->assertSame('contacted', SavedLead::find($email->saved_lead_id)->contact_status);
        $this->assertSame(LeadCenterLead::STATUS_CONNECTED, $center->fresh()->status);
        $this->assertSame([EmailTrackingEvent::QUEUED, EmailTrackingEvent::ACCEPTED], EmailTrackingEvent::orderBy('id')->pluck('event_type')->all());
        $accepted = EmailTrackingEvent::where('event_type', EmailTrackingEvent::ACCEPTED)->sole();
        $this->assertSame(EmailTrackingEvent::SOURCE_GMAIL, $accepted->event_source);

        // Running the job again (duplicate delivery) adds nothing
        $this->runJob($email->id);
        $this->assertSame(1, EmailTrackingEvent::where('event_type', EmailTrackingEvent::ACCEPTED)->count());
    }

    public function test_failed_and_unknown_outcomes_are_recorded_as_such(): void
    {
        $user = $this->trackingSender();
        $this->connect($user);
        $this->respondWith(self::SEND_URL, new \Illuminate\Http\Client\ConnectionException('timed out'));
        $draft = $this->draftFor($user);
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload());

        $email = OutboundEmail::sole();
        $this->assertTrue($email->isUnknown());
        $this->assertSame(1, EmailTrackingEvent::where('event_type', EmailTrackingEvent::UNKNOWN)->count());
        $this->assertSame(0, EmailTrackingEvent::where('event_type', EmailTrackingEvent::ACCEPTED)->count());
        $this->assertSame('not_contacted', SavedLead::find($email->saved_lead_id)->contact_status);
    }

    public function test_recording_a_reply_updates_lead_center_and_my_leads(): void
    {
        [$user, $email, $lead] = $this->sentEmail();
        $center = $this->centerLead($lead, LeadCenterLead::STATUS_CONNECTED);

        $this->actingAs($user)->post("/user/email-analytics/emails/{$email->id}/reply", [
            'reply_status' => 'meeting_requested',
            'reply_received_at' => now()->format('Y-m-d\TH:i'),
            'notes' => 'Wants a call on Tuesday',
        ])->assertSessionHas('success');

        $reply = EmailReplyRecord::sole();
        $this->assertSame('meeting_requested', $reply->reply_status);
        $this->assertSame($user->id, $reply->recorded_by_user_id);
        $this->assertSame('manual', $reply->source);
        $this->assertSame('responded', $lead->fresh()->contact_status);
        $this->assertSame(LeadCenterLead::STATUS_RESPONDED, $center->fresh()->status);
        $this->assertSame(1, EmailTrackingEvent::where('event_type', EmailTrackingEvent::REPLY_RECORDED)->where('event_source', EmailTrackingEvent::SOURCE_USER)->count());

        $this->actingAs($user)->get("/user/email-analytics/emails/{$email->id}")->assertOk()
            ->assertSee('Meeting requested')->assertSee('Wants a call on Tuesday')
            ->assertSee('Automatic reply detection is not available');
    }

    public function test_reply_can_be_corrected_and_removed_with_an_audit_trail(): void
    {
        [$user, $email, $lead] = $this->sentEmail();
        $teammate = $user; // same person corrects here; team case covered in analytics tests

        $post = fn (string $status) => $this->actingAs($teammate)->post("/user/email-analytics/emails/{$email->id}/reply", [
            'reply_status' => $status, 'reply_received_at' => now()->format('Y-m-d\TH:i'),
        ]);

        $post('not_interested')->assertSessionHas('success');
        $this->assertTrue(EmailSuppression::isSuppressed($user->id, $email->to_email));
        $this->assertSame('not_interested', $lead->fresh()->contact_status);

        // Correction: it was actually "interested" -> the block created by that record is lifted
        $post('interested')->assertSessionHas('success');
        $this->assertSame(1, EmailReplyRecord::count());
        $this->assertSame('interested', EmailReplyRecord::sole()->reply_status);
        $this->assertFalse(EmailSuppression::isSuppressed($user->id, $email->to_email));
        $updated = EmailTrackingEvent::where('event_type', EmailTrackingEvent::REPLY_UPDATED)->sole();
        $this->assertSame(['reply_status' => 'interested', 'previous_status' => 'not_interested'], $updated->metadata);

        $this->actingAs($user)->delete("/user/email-analytics/emails/{$email->id}/reply")->assertSessionHas('success');
        $this->assertSame(0, EmailReplyRecord::count());
        $this->assertSame(1, EmailTrackingEvent::where('event_type', EmailTrackingEvent::REPLY_REMOVED)->count());
        $this->assertDatabaseHas('email_audit_logs', ['action' => 'reply_removed', 'outbound_email_id' => $email->id, 'user_id' => $user->id]);
    }

    public function test_reply_validation(): void
    {
        [$user, $email] = $this->sentEmail();
        $url = "/user/email-analytics/emails/{$email->id}/reply";

        $this->actingAs($user)->post($url, ['reply_status' => 'loves_us', 'reply_received_at' => now()->toDateTimeString()])->assertSessionHasErrors('reply_status');
        $this->actingAs($user)->post($url, ['reply_status' => 'interested', 'reply_received_at' => now()->addDay()->toDateTimeString()])->assertSessionHasErrors('reply');
        $this->actingAs($user)->post($url, ['reply_status' => 'interested', 'reply_received_at' => now()->subYear()->toDateTimeString()])->assertSessionHasErrors('reply');
        $this->actingAs($user)->post($url, ['reply_status' => 'interested', 'reply_received_at' => now()->toDateTimeString(), 'notes' => str_repeat('x', 1001)])->assertSessionHasErrors('notes');

        // Emails that were never sent cannot have replies
        $email->forceFill(['status' => OutboundEmail::STATUS_CANCELLED])->save();
        $this->actingAs($user)->post($url, ['reply_status' => 'interested', 'reply_received_at' => now()->toDateTimeString()])->assertSessionHasErrors('reply');
        $this->assertSame(0, EmailReplyRecord::count());
    }

    public function test_not_interested_blocks_further_outreach_and_cancels_follow_ups(): void
    {
        [$user, $email, $lead] = $this->sentEmail();
        $center = $this->centerLead($lead, LeadCenterLead::STATUS_CONNECTED);
        $this->actingAs($user)->post('/user/email-follow-ups', ['saved_lead_id' => $lead->id, 'due_date' => today()->addDays(3)->toDateString()])->assertSessionHas('success');

        $this->actingAs($user)->post("/user/email-analytics/emails/{$email->id}/reply", [
            'reply_status' => 'not_interested', 'reply_received_at' => now()->toDateTimeString(),
        ]);

        $this->assertSame(EmailFollowUp::CANCELLED, EmailFollowUp::sole()->status);
        $this->assertSame('not_interested', EmailFollowUp::sole()->cancel_reason);
        $this->assertSame(LeadCenterLead::STATUS_CLOSED, $center->fresh()->status);
        $this->assertNull($lead->fresh()->follow_up_date);

        // No new follow-up, and a new draft to the same address cannot be sent
        $this->actingAs($user)->post('/user/email-follow-ups', ['saved_lead_id' => $lead->id, 'due_date' => today()->addDays(5)->toDateString()])
            ->assertSessionHasErrors('follow_up');
        $draft2 = $this->draftFor($user, [], [], $lead);
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft2->id}/send", $this->sendPayload())->assertSessionHasErrors('send');
        $this->assertSame(1, OutboundEmail::count());
    }

    public function test_converted_is_never_downgraded_by_a_later_reply(): void
    {
        [$user, $email, $lead] = $this->sentEmail();
        $center = $this->centerLead($lead);
        $lead->forceFill(['contact_status' => 'converted'])->save();
        $center->forceFill(['status' => LeadCenterLead::STATUS_CONVERTED])->save();

        $this->actingAs($user)->post("/user/email-analytics/emails/{$email->id}/reply", [
            'reply_status' => 'interested', 'reply_received_at' => now()->toDateTimeString(),
        ]);

        $this->assertSame('converted', $lead->fresh()->contact_status);
        $this->assertSame(LeadCenterLead::STATUS_CONVERTED, $center->fresh()->status);
    }

    public function test_needs_follow_up_reply_can_schedule_a_reminder(): void
    {
        [$user, $email, $lead] = $this->sentEmail();

        $this->actingAs($user)->post("/user/email-analytics/emails/{$email->id}/reply", [
            'reply_status' => 'needs_follow_up', 'reply_received_at' => now()->toDateTimeString(),
            'follow_up_date' => today()->addWeek()->toDateString(), 'notes' => 'Busy until next week',
        ])->assertSessionHas('success');

        $followUp = EmailFollowUp::sole();
        $this->assertSame($email->id, $followUp->outbound_email_id);
        $this->assertSame(today()->addWeek()->toDateString(), $followUp->due_date->toDateString());
        $this->assertSame('follow_up', $lead->fresh()->contact_status);
        $this->assertSame(today()->addWeek()->toDateString(), $lead->fresh()->follow_up_date->toDateString());
        $this->assertSame('email', $lead->fresh()->follow_up_source);
    }

    public function test_follow_up_lifecycle_and_lead_date_mirror(): void
    {
        [$user, $email, $lead] = $this->sentEmail();
        $this->actingAs($user)->post('/user/email-follow-ups', [
            'saved_lead_id' => $lead->id, 'outbound_email_id' => $email->id, 'due_date' => today()->addDays(10)->toDateString(), 'notes' => 'Second nudge',
        ])->assertSessionHas('success');
        $this->actingAs($user)->post('/user/email-follow-ups', ['saved_lead_id' => $lead->id, 'due_date' => today()->addDays(2)->toDateString()]);

        $this->assertSame(today()->addDays(2)->toDateString(), $lead->fresh()->follow_up_date->toDateString(), 'Earliest open reminder is mirrored');

        $early = EmailFollowUp::where('due_date', today()->addDays(2)->toDateString())->sole();
        $this->actingAs($user)->post("/user/email-follow-ups/{$early->id}/complete", ['notes' => 'Called, left message'])->assertSessionHas('success');
        $this->assertSame(EmailFollowUp::COMPLETED, $early->fresh()->status);
        $this->assertSame($user->id, $early->fresh()->completed_by_user_id);
        $this->assertSame(today()->addDays(10)->toDateString(), $lead->fresh()->follow_up_date->toDateString());

        // Closing an already closed reminder is a no-op
        $this->actingAs($user)->post("/user/email-follow-ups/{$early->id}/complete")->assertSessionHas('error');

        $late = EmailFollowUp::where('status', 'open')->sole();
        $this->actingAs($user)->post("/user/email-follow-ups/{$late->id}/cancel")->assertSessionHas('success');
        $this->assertNull($lead->fresh()->follow_up_date);

        $this->assertSame(2, EmailTrackingEvent::where('event_type', EmailTrackingEvent::FOLLOW_UP_SCHEDULED)->count());
        $this->assertSame(1, EmailTrackingEvent::where('event_type', EmailTrackingEvent::FOLLOW_UP_COMPLETED)->count());
        $this->assertSame(1, EmailTrackingEvent::where('event_type', EmailTrackingEvent::FOLLOW_UP_CANCELLED)->count());
        $this->assertSame(1, OutboundEmail::count(), 'Follow-ups never send email');
    }

    public function test_follow_up_date_validation_and_manual_dates_are_kept(): void
    {
        [$user, , $lead] = $this->sentEmail();
        $lead->forceFill(['follow_up_date' => today()->addDays(30)->toDateString(), 'follow_up_source' => 'whatsapp'])->save();

        $this->actingAs($user)->post('/user/email-follow-ups', ['saved_lead_id' => $lead->id, 'due_date' => today()->subDay()->toDateString()])->assertSessionHasErrors('due_date');
        $this->actingAs($user)->post('/user/email-follow-ups', ['saved_lead_id' => $lead->id, 'due_date' => 'tomorrow'])->assertSessionHasErrors('due_date');

        $this->actingAs($user)->post('/user/email-follow-ups', ['saved_lead_id' => $lead->id, 'due_date' => today()->addDay()->toDateString()]);
        $followUp = EmailFollowUp::sole();
        $this->actingAs($user)->post("/user/email-follow-ups/{$followUp->id}/cancel");

        // The manual WhatsApp date was replaced while the reminder was open, but a date this
        // module did not set is never cleared by it
        $lead = $lead->fresh();
        $lead->forceFill(['follow_up_date' => today()->addDays(30)->toDateString(), 'follow_up_source' => 'whatsapp'])->save();
        \App\Services\EmailTracking\LeadSync::syncFollowUpDate($lead->fresh());
        $this->assertSame('whatsapp', $lead->fresh()->follow_up_source);
        $this->assertNotNull($lead->fresh()->follow_up_date);
    }

    public function test_unsubscribe_records_opt_out_once_and_closes_the_lead(): void
    {
        [$user, $email, $lead] = $this->sentEmail();
        $center = $this->centerLead($lead, LeadCenterLead::STATUS_CONNECTED);
        $this->actingAs($user)->post('/user/email-follow-ups', ['saved_lead_id' => $lead->id, 'due_date' => today()->addDays(3)->toDateString()]);
        auth()->logout();

        $url = app(\App\Services\Gmail\OutboundEmailService::class)->unsubscribeUrl($email);
        $confirm = str_replace('/email/unsubscribe/', '/email/unsubscribe/', $url);
        $this->post($confirm)->assertOk();
        $this->post($confirm, ['List-Unsubscribe' => 'One-Click'])->assertOk();

        $this->assertSame(1, EmailTrackingEvent::where('event_type', EmailTrackingEvent::OPTED_OUT)->count());
        $this->assertSame(EmailTrackingEvent::SOURCE_UNSUBSCRIBE, EmailTrackingEvent::where('event_type', EmailTrackingEvent::OPTED_OUT)->sole()->event_source);
        $this->assertSame(EmailFollowUp::CANCELLED, EmailFollowUp::sole()->status);
        $this->assertSame('not_interested', $lead->fresh()->contact_status);
        $this->assertSame(LeadCenterLead::STATUS_CLOSED, $center->fresh()->status);
    }

    public function test_lead_timeline_is_chronological_with_sources(): void
    {
        [$user, $email, $lead] = $this->sentEmail();
        $this->travel(1)->hours();
        $this->actingAs($user)->post("/user/email-analytics/emails/{$email->id}/reply", [
            'reply_status' => 'interested', 'reply_received_at' => now()->toDateTimeString(),
        ]);
        $this->actingAs($user)->post('/user/email-follow-ups', ['saved_lead_id' => $lead->id, 'due_date' => today()->addDays(4)->toDateString()]);

        $page = $this->actingAs($user)->get("/user/email-analytics/leads/{$lead->id}")->assertOk();
        $page->assertSeeInOrder(['AI email draft approved', 'Email queued for sending', 'Email accepted by Gmail', 'Reply marked as received', 'Follow-up scheduled', 'Follow-up due']);
        $page->assertSee('Source: Gmail API')->assertSee('Source: User action')->assertSee('Source: Application system');
    }
}
