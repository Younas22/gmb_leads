<?php

namespace Tests\Feature\EmailTracking;

use App\Models\EmailFollowUp;
use App\Models\EmailReplyRecord;
use App\Models\EmailTrackingEvent;
use App\Models\EmailTrackingLink;
use App\Models\EmailTrackingSetting;
use App\Models\OutboundEmail;
use App\Models\User;
use App\Services\EmailTracking\EmailAnalytics;
use Illuminate\Support\Str;

/** Phase 4: analytics numbers, tenant isolation, plan gating and retention. */
class AnalyticsAndAccessTest extends TrackingTestCase
{
    private function email(User $owner, array $attrs = [], ?User $sender = null): OutboundEmail
    {
        $sender ??= $owner;

        return OutboundEmail::create(array_merge([
            'owner_user_id' => $owner->id, 'user_id' => $sender->id,
            'saved_lead_id' => $this->leadFor($owner)->id,
            'idempotency_key' => (string) Str::uuid(),
            'from_email' => 'ayesha@gmail.com', 'to_email' => Str::random(6) . '@biz.example',
            'subject' => 'Hello', 'body' => 'Body', 'status' => 'sent', 'link_tracking' => false,
            'queued_at' => now(), 'sent_at' => now(),
        ], $attrs));
    }

    private function addClicks(OutboundEmail $email, string $classification, int $n = 1): void
    {
        $link = EmailTrackingLink::firstOrCreate(['outbound_email_id' => $email->id, 'link_position' => 1], [
            'owner_user_id' => $email->owner_user_id, 'token_hash' => hash('sha256', Str::random(40)), 'original_url' => 'https://x.example',
        ]);
        for ($i = 0; $i < $n; $i++) {
            EmailTrackingEvent::create([
                'owner_user_id' => $email->owner_user_id, 'outbound_email_id' => $email->id, 'tracking_link_id' => $link->id,
                'event_type' => EmailTrackingEvent::LINK_CLICK, 'event_source' => EmailTrackingEvent::SOURCE_REDIRECT,
                'classification' => $classification, 'occurred_at' => now(),
            ]);
        }
    }

    private function reply(OutboundEmail $email, string $status = 'interested'): void
    {
        EmailReplyRecord::create([
            'owner_user_id' => $email->owner_user_id, 'outbound_email_id' => $email->id, 'saved_lead_id' => $email->saved_lead_id,
            'reply_status' => $status, 'reply_received_at' => now(),
        ]);
    }

    private function filters(array $extra = []): array
    {
        return array_merge(['from' => today()->subDays(29)->startOfDay(), 'to' => now()->endOfDay()], $extra);
    }

    public function test_summary_uses_matching_denominators_and_never_invents_metrics(): void
    {
        $user = $this->trackingSender();
        $tracked1 = $this->email($user, ['link_tracking' => true]);
        $tracked2 = $this->email($user, ['link_tracking' => true]);
        $plain = $this->email($user);
        $this->email($user, ['status' => 'failed', 'sent_at' => null]);
        $unknown = $this->email($user, ['status' => 'failed', 'outcome_unknown' => true, 'sent_at' => null]);
        $this->email($user, ['status' => 'pending', 'sent_at' => null]);
        $this->email($user, ['status' => 'sent', 'queued_at' => now()->subDays(60), 'sent_at' => now()->subDays(60)]); // outside period

        $this->addClicks($tracked1, EmailTrackingEvent::HUMAN, 3);
        $this->addClicks($tracked1, EmailTrackingEvent::SCANNER, 2);
        $this->addClicks($tracked2, EmailTrackingEvent::SCANNER);
        $this->addClicks($tracked2, EmailTrackingEvent::INTERNAL);
        $this->reply($tracked1);
        $this->reply($plain, 'not_interested');
        $this->reply($unknown, 'interested'); // a reply proves it went out, but it is not an "accepted" email
        EmailTrackingEvent::create(['owner_user_id' => $user->id, 'outbound_email_id' => $plain->id, 'event_type' => EmailTrackingEvent::OPTED_OUT, 'event_source' => EmailTrackingEvent::SOURCE_UNSUBSCRIBE, 'occurred_at' => now()]);

        $s = app(EmailAnalytics::class)->summary($user, $this->filters());

        $this->assertSame(6, $s['queued']);
        $this->assertSame(3, $s['accepted']);
        $this->assertSame(1, $s['failed']);
        $this->assertSame(1, $s['unknown']);
        $this->assertSame(1, $s['in_progress']);
        $this->assertSame(7, $s['link_requests']);
        $this->assertSame(3, $s['human_clicks']);
        $this->assertSame(3, $s['scanner_requests']);
        $this->assertSame(1, $s['internal_requests']);
        $this->assertSame(1, $s['unique_clicks_estimate'], 'Only one email had a probable-person click');
        $this->assertSame(50.0, $s['click_rate_estimate'], '1 of 2 accepted tracked emails');
        $this->assertSame(3, $s['replies']);
        $this->assertSame(2, $s['replies_on_accepted']);
        $this->assertSame(66.7, $s['reply_rate'], '2 replies / 3 accepted');
        $this->assertSame(['interested' => 2, 'not_interested' => 1], $s['replies_by_status']);
        $this->assertSame(1, $s['unsubscribes']);
        $this->assertArrayNotHasKey('open_rate', $s);
        // Phase 5: delivery exists only as Resend evidence; with Gmail-only data it is unavailable, never 0
        $this->assertNull($s['delivered']);
        $this->assertNull($s['delivery_rate']);
    }

    public function test_rates_are_empty_rather_than_zero_without_a_denominator(): void
    {
        $user = $this->trackingSender();
        $this->email($user, ['status' => 'failed', 'sent_at' => null]);

        $s = app(EmailAnalytics::class)->summary($user, $this->filters());

        $this->assertNull($s['reply_rate']);
        $this->assertNull($s['click_rate_estimate']);
    }

    public function test_dashboard_page_filters_and_labels(): void
    {
        $user = $this->trackingSender();
        $old = $this->email($user, ['queued_at' => now()->subDays(3), 'subject' => 'Three days ago']);
        $today = $this->email($user, ['subject' => 'Sent today']);
        $failed = $this->email($user, ['status' => 'failed', 'sent_at' => null, 'subject' => 'Gmail refused']);

        $this->actingAs($user)->get('/user/email-analytics')->assertOk()
            ->assertSee('Email Analytics')->assertSee('Accepted by Gmail')->assertSee('Not proof of inbox delivery')
            ->assertSee('Estimated unique clicks')->assertSee('recorded by you')
            ->assertDontSee('Open rate')->assertDontSee('Delivery rate')
            ->assertSee('Three days ago')->assertSee('Sent today');

        $this->actingAs($user)->get('/user/email-analytics?range=today')->assertOk()->assertSee('Sent today')->assertDontSee('Three days ago');
        $this->actingAs($user)->get('/user/email-analytics?range=custom&from=' . today()->subDays(4)->toDateString() . '&to=' . today()->subDays(2)->toDateString())
            ->assertOk()->assertSee('Three days ago')->assertDontSee('Sent today');
        $this->actingAs($user)->get('/user/email-analytics?status=failed')->assertOk()->assertSee('Gmail refused')->assertDontSee('Sent today');
        $this->actingAs($user)->get('/user/email-analytics?lead=' . $today->saved_lead_id)->assertOk()->assertSee('Sent today')->assertDontSee('Gmail refused');

        $this->actingAs($user)->get('/user/email-analytics?range=custom&from=2026-02-10&to=2026-01-01')->assertSessionHasErrors('to');
        $this->actingAs($user)->get('/user/email-analytics?range=forever')->assertSessionHasErrors('range');
    }

    public function test_accounts_never_see_each_others_data(): void
    {
        $alice = $this->trackingSender();
        $bob = $this->trackingSender();
        $aliceEmail = $this->email($alice, ['subject' => 'Alice secret subject']);
        $this->addClicks($aliceEmail, EmailTrackingEvent::HUMAN);
        $this->reply($aliceEmail);

        $s = app(EmailAnalytics::class)->summary($bob, $this->filters());
        $this->assertSame(0, $s['queued']);
        $this->assertSame(0, $s['link_requests']);
        $this->assertSame(0, $s['replies']);

        $this->actingAs($bob)->get('/user/email-analytics')->assertOk()->assertDontSee('Alice secret subject');
        $this->actingAs($bob)->get("/user/email-analytics/emails/{$aliceEmail->id}")->assertNotFound();
        $this->actingAs($bob)->get("/user/email-analytics/leads/{$aliceEmail->saved_lead_id}")->assertNotFound();
        $this->actingAs($bob)->get("/user/email-analytics?lead={$aliceEmail->saved_lead_id}")->assertNotFound();
        $this->actingAs($bob)->post("/user/email-analytics/emails/{$aliceEmail->id}/reply", ['reply_status' => 'converted', 'reply_received_at' => now()->toDateTimeString()])->assertNotFound();
        $this->actingAs($bob)->delete("/user/email-analytics/emails/{$aliceEmail->id}/reply")->assertNotFound();
        $this->actingAs($bob)->post('/user/email-follow-ups', ['saved_lead_id' => $aliceEmail->saved_lead_id, 'due_date' => today()->addDay()->toDateString()])->assertNotFound();

        $followUp = EmailFollowUp::create(['owner_user_id' => $alice->id, 'saved_lead_id' => $aliceEmail->saved_lead_id, 'due_date' => today(), 'status' => 'open']);
        $this->actingAs($bob)->post("/user/email-follow-ups/{$followUp->id}/complete")->assertNotFound();
        $this->actingAs($bob)->get('/user/email-follow-ups')->assertOk()->assertDontSee($aliceEmail->lead->name);
        $this->assertSame('interested', EmailReplyRecord::sole()->reply_status);

        // Account filters cannot point at another account's sender or member
        $bobAccount = $this->connect($bob, ['email' => 'bob@gmail.com', 'google_sub' => 'bob-sub']);
        $this->actingAs($alice)->get("/user/email-analytics?account={$bobAccount->id}")->assertSessionHasErrors('account');
        $this->actingAs($alice)->get("/user/email-analytics?member={$bob->id}")->assertSessionHasErrors('member');
    }

    public function test_team_members_share_the_company_view_and_can_record_replies(): void
    {
        $company = $this->trackingSender([], ['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);
        $memberEmail = $this->email($company, ['subject' => 'Sent by member'], $member);

        $this->actingAs($company)->get("/user/email-analytics?member={$member->id}")->assertOk()->assertSee('Sent by member');
        $this->actingAs($member)->get('/user/email-analytics')->assertOk()->assertSee('Sent by member');

        $this->actingAs($member)->post("/user/email-analytics/emails/{$memberEmail->id}/reply", ['reply_status' => 'interested', 'reply_received_at' => now()->toDateTimeString()])
            ->assertSessionHas('success');
        $this->actingAs($company)->post("/user/email-analytics/emails/{$memberEmail->id}/reply", ['reply_status' => 'converted', 'reply_received_at' => now()->toDateTimeString()])
            ->assertSessionHas('success');

        $reply = EmailReplyRecord::sole();
        $this->assertSame($member->id, $reply->recorded_by_user_id);
        $this->assertSame($company->id, $reply->updated_by_user_id);
        $this->actingAs($company)->get("/user/email-analytics/emails/{$memberEmail->id}")->assertSee('Recorded manually by ' . $member->name, false);
    }

    public function test_features_are_off_by_default_and_each_is_gated(): void
    {
        $this->assertFalse(config('ai.feature_defaults.email_tracking_enabled'));
        $this->assertFalse(config('ai.feature_defaults.email_analytics_enabled'));
        $this->assertFalse(config('ai.feature_defaults.email_followups_enabled'));

        // Existing plan with Gmail sending only: no new pages, no tracking
        $user = $this->sender();
        $this->actingAs($user)->get('/user/email-analytics')->assertRedirect(route('user.subscription'));
        $this->actingAs($user)->get('/user/email-follow-ups')->assertRedirect(route('user.subscription'));
        $this->actingAs($user)->get('/user/email-sending')->assertOk()
            ->assertDontSee('Email Analytics')->assertDontSee('Track link clicks')->assertSee('Not included in your plan');

        // Analytics only
        $analytics = $this->sender(['email_analytics_enabled' => '1']);
        $this->actingAs($analytics)->get('/user/email-analytics')->assertOk();
        $this->actingAs($analytics)->get('/user/email-follow-ups')->assertRedirect(route('user.subscription'));
        $this->actingAs($analytics)->get('/user/dashboard')->assertSee('Email Analytics')->assertDontSee('fa-bell w-5', false);

        // Follow-ups only
        $followUps = $this->sender(['email_followups_enabled' => '1']);
        $this->actingAs($followUps)->get('/user/email-follow-ups')->assertOk();
        $this->actingAs($followUps)->get('/user/email-analytics')->assertRedirect(route('user.subscription'));

        // Gmail sending permissions unchanged by Phase 4 keys
        $noGmail = $this->seller(['email_analytics_enabled' => '1', 'email_tracking_enabled' => '1']);
        $this->actingAs($noGmail)->get('/user/email-sending')->assertRedirect(route('user.subscription'));
    }

    public function test_follow_up_reminders_show_on_dashboard_and_sidebar(): void
    {
        $user = $this->trackingSender();
        $email = $this->email($user);
        EmailFollowUp::create(['owner_user_id' => $user->id, 'saved_lead_id' => $email->saved_lead_id, 'due_date' => today()->subDay(), 'status' => 'open']);
        EmailFollowUp::create(['owner_user_id' => $user->id, 'saved_lead_id' => $email->saved_lead_id, 'due_date' => today(), 'status' => 'open']);
        EmailFollowUp::create(['owner_user_id' => $user->id, 'saved_lead_id' => $email->saved_lead_id, 'due_date' => today()->addDays(3), 'status' => 'open']);

        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertSee('data-testid="dashboard-follow-ups"', false)->assertSee('follow-up due today')
            // Regression: an if/endif glued to a word used to compile only half and push the page out of <main>
            ->assertDontSee('@if(', false)->assertDontSee('@endif', false)
            ->assertSeeInOrder(['<main', 'data-testid="dashboard-follow-ups"', 'due today', '>1</strong>', 'overdue', 'View follow-ups', '</main>'], false);

        $page = $this->actingAs($user)->get('/user/email-follow-ups')->assertOk();
        $page->assertSee('Overdue (1)')->assertSee('Upcoming (2)');

        $s = app(EmailAnalytics::class)->summary($user, $this->filters());
        $this->assertSame(['overdue' => 1, 'today' => 1, 'next_7_days' => 1], $s['follow_ups']);
    }

    public function test_large_lists_are_paginated(): void
    {
        $user = $this->trackingSender();
        $lead = $this->leadFor($user);
        for ($i = 1; $i <= 60; $i++) {
            $this->email($user, ['saved_lead_id' => $lead->id, 'subject' => sprintf('Mail %02d', $i), 'queued_at' => now()->subMinutes($i)]);
        }

        $first = $this->actingAs($user)->get('/user/email-analytics')->assertOk();
        $first->assertSee('Emails (60)')->assertSee('Mail 01')->assertSee('Mail 25')->assertDontSee('Mail 26');
        $this->actingAs($user)->get('/user/email-analytics?page=3')->assertOk()->assertSee('Mail 51')->assertDontSee('Mail 50');
        $this->actingAs($user)->get("/user/email-analytics/leads/{$lead->id}")->assertOk()->assertSee('Mail 01')->assertDontSee('Mail 21');
    }

    public function test_retention_cleanup_deletes_only_expired_click_data(): void
    {
        $short = $this->trackingSender();
        $default = $this->trackingSender();
        EmailTrackingSetting::for($short)->fill(['link_tracking_enabled' => true, 'retention_days' => 30])->save();

        $a = $this->email($short, ['link_tracking' => true]);
        $b = $this->email($default, ['link_tracking' => true]);
        $this->addClicks($a, EmailTrackingEvent::HUMAN);
        $this->addClicks($b, EmailTrackingEvent::HUMAN);
        EmailTrackingEvent::query()->update(['occurred_at' => now()->subDays(45)]);
        EmailTrackingEvent::create(['owner_user_id' => $short->id, 'outbound_email_id' => $a->id, 'event_type' => EmailTrackingEvent::ACCEPTED, 'event_source' => EmailTrackingEvent::SOURCE_GMAIL, 'occurred_at' => now()->subDays(400)]);

        $this->artisan('email:prune-tracking')->assertSuccessful();

        $this->assertSame(0, EmailTrackingEvent::where('outbound_email_id', $a->id)->where('event_type', EmailTrackingEvent::LINK_CLICK)->count(), '30-day sender: removed');
        $this->assertSame(1, EmailTrackingEvent::where('outbound_email_id', $b->id)->where('event_type', EmailTrackingEvent::LINK_CLICK)->count(), '90-day default: kept');
        $this->assertSame(1, EmailTrackingEvent::where('event_type', EmailTrackingEvent::ACCEPTED)->count(), 'Send history is not tracking data');

        // Links past their lifetime stop working
        EmailTrackingLink::query()->update(['created_at' => now()->subDays(400)]);
        $this->artisan('email:prune-tracking')->assertSuccessful();
        $this->assertSame(0, EmailTrackingLink::count());

        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $this->assertNotNull($events->first(fn ($e) => str_contains($e->command, 'email:prune-tracking')));
    }

    public function test_phase_4_leaves_gmail_scopes_extension_api_and_queues_unchanged(): void
    {
        // Send-only by default; read-only access is requested only with GMAIL_REPLY_DETECTION=true
        $this->assertSame(['openid', 'email', 'https://www.googleapis.com/auth/gmail.send'], config('gmail.scopes'));
        foreach (['gmail.metadata', 'gmail.modify', 'mail.google.com'] as $scope) {
            $this->assertStringNotContainsString($scope, file_get_contents(config_path('gmail.php')));
        }

        // No new API routes; extension routes still registered
        $api = collect(app('router')->getRoutes()->getRoutes())->filter(fn ($r) => str_starts_with($r->uri(), 'api/'));
        $this->assertTrue($api->contains(fn ($r) => str_contains($r->uri(), 'extension')));
        $this->assertFalse($api->contains(fn ($r) => str_contains($r->uri(), 'tracking') || str_contains($r->uri(), 'analytics')));

        // No new queue workers: tracking is recorded inline, cleanup is a short daily command
        $workers = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->filter(fn ($e) => str_contains($e->command, 'queue:work'));
        $this->assertCount(2, $workers);

        // No automated sending, inbox reading or pixels anywhere in the Phase 4 code
        foreach (glob(app_path('Services/EmailTracking/*.php')) as $file) {
            // Code only: comments may explain what is deliberately NOT done
            $code = collect(token_get_all(file_get_contents($file)))
                ->reject(fn ($t) => is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true))
                ->map(fn ($t) => is_array($t) ? $t[1] : $t)->implode('');
            $this->assertDoesNotMatchRegularExpression('/SendGmailMessage|->send\(|messages\/send|gmail\.readonly|users\/me\/messages\?|image\/gif|1x1/i', $code, basename($file));
        }
    }
}
