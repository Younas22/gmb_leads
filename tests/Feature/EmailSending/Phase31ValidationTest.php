<?php

namespace Tests\Feature\EmailSending;

use App\Models\ConnectedEmailAccount;
use App\Models\EmailAuditLog;
use App\Models\OutboundEmail;
use App\Models\PackageFeature;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

/** Regression tests for the Phase 3.1 validation fixes. */
class Phase31ValidationTest extends GmailTestCase
{
    private function startConnect(User $user): string
    {
        $this->actingAs($user)->post('/user/email-sending/google/connect')->assertRedirect();

        return session('gmail_oauth')['state'];
    }

    // ── Feature access ───────────────────────────────────────────────────

    public function test_gmail_sending_is_off_unless_the_package_enables_it(): void
    {
        $this->assertFalse(config('ai.feature_defaults.gmail_sending_enabled'));

        $plain = $this->seller(); // package without the key
        $this->actingAs($plain)->get('/user/email-sending')->assertRedirect(route('user.subscription'));
        $this->actingAs($plain)->get('/user/dashboard')->assertDontSee('Email Sending');

        foreach (['1', 'true', 'yes'] as $on) {
            $user = $this->seller(['gmail_sending_enabled' => $on]);
            $this->actingAs($user)->get('/user/email-sending')->assertOk();
        }
        foreach (['0', 'false', ''] as $off) {
            $user = $this->seller(['gmail_sending_enabled' => $off]);
            $this->actingAs($user)->get('/user/email-sending')->assertRedirect(route('user.subscription'));
        }
    }

    public function test_draft_page_hides_gmail_panel_without_the_feature(): void
    {
        $user = $this->seller(); // feature off
        $offer = $this->offerFor($user);
        $lead = $this->leadFor($user, ['email' => 'info@smiledental.example']);
        $this->fakeAi($this->happyAi($offer->id));
        $this->actingAs($user)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();
        $draft = \App\Models\AiEmailDraft::sole();
        $this->post("/user/ai-outreach/drafts/{$draft->id}/approve");

        $this->get('/user/ai-outreach/analyses/' . \App\Models\AiLeadAnalysis::sole()->id)->assertOk()
            ->assertDontSee('Send via Gmail')->assertDontSee('Review before sending');
    }

    public function test_team_member_inherits_company_feature_and_shares_the_daily_limit(): void
    {
        $company = $this->sender(['email_sends_daily' => '2'], ['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);
        $this->connect($company, ['email' => 'owner@gmail.com', 'google_sub' => 'sub-owner']);
        $this->connect($member, ['email' => 'member@gmail.com', 'google_sub' => 'sub-member']);
        $this->gmailAccepts();

        $this->actingAs($member)->get('/user/email-sending')->assertOk();

        $d1 = $this->draftFor($company, [], ['email' => 'a@lead-one.example']);
        $d2 = $this->draftFor($company, [], ['email' => 'b@lead-two.example']);
        $d3 = $this->draftFor($company, [], ['email' => 'c@lead-three.example']);

        $this->actingAs($company)->post("/user/email-sending/drafts/{$d1->id}/send", $this->sendPayload(['recipient' => 'a@lead-one.example']))->assertSessionHas('success');
        $this->actingAs($member)->post("/user/email-sending/drafts/{$d2->id}/send", $this->sendPayload(['recipient' => 'b@lead-two.example']))->assertSessionHas('success');
        $this->actingAs($member)->post("/user/email-sending/drafts/{$d3->id}/send", $this->sendPayload(['recipient' => 'c@lead-three.example']))
            ->assertSessionHasErrors(['send' => "You have reached today's sending limit (2)."]);

        $this->assertSame(2, OutboundEmail::where('status', 'sent')->count());

        // Disabling the feature on the company plan removes it for members too
        PackageFeature::where('feature_key', 'gmail_sending_enabled')->update(['feature_value' => '0']);
        $this->actingAs($member->fresh())->get('/user/email-sending')->assertRedirect(route('user.subscription'));
    }

    public function test_unlimited_daily_sends(): void
    {
        $user = $this->sender(['email_sends_daily' => 'unlimited']);
        $this->assertSame(-1, app(\App\Services\Gmail\OutboundEmailService::class)->dailyLimit($user));
        $this->actingAs($user)->get('/user/email-sending')->assertSee('unlimited');
    }

    // ── OAuth: reconnect / wrong account ─────────────────────────────────

    public function test_reconnect_after_revocation_restores_the_account(): void
    {
        $user = $this->sender();
        $account = $this->connect($user, ['status' => 'needs_reconnect', 'access_token' => null]);

        $this->actingAs($user)->get('/user/email-sending')->assertSee('Reconnect Gmail');
        $state = $this->startConnect($user);
        $this->respond(self::TOKEN_URL, $this->tokenResponse(['refresh_token' => '1//fresh-after-reconnect']));
        $this->get("/user/email-sending/google/callback?state={$state}&code=c")->assertSessionHas('success');

        $account->refresh();
        $this->assertSame('active', $account->status);
        $this->assertSame('1//fresh-after-reconnect', $account->refresh_token);
        $this->assertSame(1, ConnectedEmailAccount::count());
    }

    public function test_revoked_refresh_token_is_not_reused_when_google_omits_a_new_one(): void
    {
        $user = $this->sender();
        $this->connect($user, ['status' => 'needs_reconnect', 'refresh_token' => '1//revoked']);
        $state = $this->startConnect($user);
        $resp = $this->tokenResponse();
        unset($resp[0]['refresh_token']);
        $this->respond(self::TOKEN_URL, $resp);

        $this->get("/user/email-sending/google/callback?state={$state}&code=c")->assertSessionHas('error');
        $this->assertSame('needs_reconnect', ConnectedEmailAccount::sole()->status);
    }

    public function test_gmail_already_connected_by_another_user_is_rejected_without_revoking(): void
    {
        $a = $this->sender();
        $b = $this->sender();
        $this->connect($a); // google-sub-123

        $state = $this->startConnect($b);
        $this->respond(self::TOKEN_URL, $this->tokenResponse()); // same Google account (sub)

        $this->get("/user/email-sending/google/callback?state={$state}&code=c")
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'already connected to another'));

        $this->assertSame(1, ConnectedEmailAccount::count());
        $this->assertSame([], $this->requestsTo(self::REVOKE_URL), "Revoking would break the other user's connection");
        $this->assertTrue(EmailAuditLog::where('action', 'gmail_connect_rejected')->exists());
    }

    public function test_switching_to_a_different_gmail_cancels_queued_emails(): void
    {
        Queue::fake();
        $user = $this->sender();
        $this->connect($user, ['email' => 'old@gmail.com', 'google_sub' => 'sub-old']);
        $draft = $this->draftFor($user);
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())->assertSessionHas('success');
        $this->assertSame('old@gmail.com', OutboundEmail::sole()->from_email);

        $state = $this->startConnect($user);
        $this->respond(self::TOKEN_URL, $this->tokenResponse([], ['sub' => 'sub-new', 'email' => 'new@gmail.com']));
        $this->get("/user/email-sending/google/callback?state={$state}&code=c")->assertSessionHas('success');

        $this->assertSame('new@gmail.com', ConnectedEmailAccount::sole()->email);
        $email = OutboundEmail::sole();
        $this->assertSame('cancelled', $email->status);
        $this->assertNull($email->draft_lock);

        // Even if the old job runs, nothing is sent from the new address
        $this->runJob($email->id);
        $this->assertSame([], $this->requestsTo(self::SEND_URL));
    }

    public function test_job_refuses_to_send_from_an_address_the_user_did_not_confirm(): void
    {
        Queue::fake();
        $user = $this->sender();
        $account = $this->connect($user);
        $draft = $this->draftFor($user);
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload());
        $account->forceFill(['email' => 'someone-else@gmail.com'])->save();

        $this->runJob(OutboundEmail::sole()->id);

        $email = OutboundEmail::sole();
        $this->assertSame('failed', $email->status);
        $this->assertSame('sender_changed', $email->error_code);
        $this->assertFalse($email->outcome_unknown);
        $this->assertSame([], $this->requestsTo(self::SEND_URL));
    }

    public function test_job_stops_if_plan_loses_the_feature_or_member_leaves(): void
    {
        Queue::fake();
        $company = $this->sender([], ['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);
        $this->connect($member, ['email' => 'member@gmail.com']);
        $d1 = $this->draftFor($company, [], ['email' => 'a@lead-one.example']);
        $d2 = $this->draftFor($company, [], ['email' => 'b@lead-two.example']);
        $this->actingAs($member)->post("/user/email-sending/drafts/{$d1->id}/send", $this->sendPayload(['recipient' => 'a@lead-one.example']));
        $this->actingAs($member)->post("/user/email-sending/drafts/{$d2->id}/send", $this->sendPayload(['recipient' => 'b@lead-two.example']));
        [$e1, $e2] = OutboundEmail::orderBy('id')->get()->all();

        PackageFeature::where('feature_key', 'gmail_sending_enabled')->update(['feature_value' => '0']);
        $this->runJob($e1->id);
        $this->assertSame('plan', $e1->fresh()->error_code);

        PackageFeature::where('feature_key', 'gmail_sending_enabled')->update(['feature_value' => '1']);
        $member->forceFill(['company_id' => null])->save();
        $this->runJob($e2->id);
        $this->assertSame('sender_unavailable', $e2->fresh()->error_code);

        $this->assertSame([], $this->requestsTo(self::SEND_URL));
    }

    // ── History ──────────────────────────────────────────────────────────

    public function test_history_distinguishes_sent_failed_and_unknown(): void
    {
        $user = $this->sender();
        $this->connect($user);
        $leads = ['ok@one.example', 'bad@two.example', 'maybe@three.example'];
        $this->respond(self::SEND_URL,
            [['id' => 'msg-accepted', 'threadId' => 't1'], 200],
            [['error' => ['code' => 400]], 400],
            [['error' => ['code' => 503]], 503],
        );

        foreach ($leads as $to) {
            $d = $this->draftFor($user, [], ['email' => $to]);
            $this->actingAs($user)->post("/user/email-sending/drafts/{$d->id}/send", $this->sendPayload(['recipient' => $to]));
        }
        [$sent, $failed, $unknown] = OutboundEmail::orderBy('id')->get()->all();

        $this->assertSame('Sent', $sent->label());
        $this->assertSame('Failed', $failed->label());
        $this->assertSame(OutboundEmail::UNKNOWN_LABEL, $unknown->label());

        $page = $this->get('/user/email-sending')->assertOk();
        $page->assertSee('msg-accepted')->assertSee(OutboundEmail::UNKNOWN_LABEL);

        $this->get('/user/email-sending?status=unknown')->assertSee('maybe@three.example')->assertDontSee('bad@two.example')->assertDontSee('ok@one.example');
        $this->get('/user/email-sending?status=failed')->assertSee('bad@two.example')->assertDontSee('maybe@three.example');
        $this->get('/user/email-sending?status=sent')->assertSee('ok@one.example')->assertDontSee('bad@two.example');
        $this->get('/user/email-sending?status=bogus')->assertSessionHasErrors('status');
    }

    // ── Ops ──────────────────────────────────────────────────────────────

    public function test_gmail_status_reports_without_printing_secrets(): void
    {
        $this->artisan('gmail:status')
            ->doesntExpectOutputToContain('test-client-secret')
            ->doesntExpectOutputToContain('test-client-id')
            ->expectsOutputToContain('Client secret set: yes')
            ->assertSuccessful();

        config(['services.google.client_id' => config('gmail.client_id')]);
        $this->artisan('gmail:status')->expectsOutputToContain('same as the Google Login client')->assertFailed();
    }

    public function test_gmail_client_never_falls_back_to_google_login_client(): void
    {
        $source = file_get_contents(config_path('gmail.php'));
        $this->assertStringNotContainsString('GOOGLE_CLIENT_ID', $source);
        $this->assertStringNotContainsString('GOOGLE_CLIENT_SECRET', $source);
    }

    public function test_scheduler_runs_bounded_mail_worker_before_ai_worker(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->values();
        $mailIdx = $events->search(fn ($e) => str_contains($e->command, 'queue:work') && str_contains($e->command, '--queue=mail'));
        $aiIdx = $events->search(fn ($e) => str_contains($e->command, 'queue:work') && str_contains($e->command, '--queue=ai'));

        $this->assertNotFalse($mailIdx);
        $this->assertNotFalse($aiIdx);
        $this->assertLessThan($aiIdx, $mailIdx, 'schedule:run is sequential: mail must not wait for the AI worker');

        $mail = $events[$mailIdx];
        foreach (['--queue=mail', '--stop-when-empty', '--max-time=50', '--tries=3', '--timeout=75'] as $flag) {
            $this->assertStringContainsString($flag, $mail->command);
        }
        $this->assertSame('* * * * *', $mail->expression);
        $this->assertTrue($mail->withoutOverlapping);
        $this->assertNotNull($events->first(fn ($e) => str_contains($e->command, 'email:recover-stuck')));
    }

    public function test_timing_budget_is_consistent(): void
    {
        $httpWorstCase = 4 * config('gmail.timeout');
        $this->assertLessThanOrEqual(config('gmail.queue.timeout'), $httpWorstCase);
        $this->assertGreaterThan(config('gmail.queue.timeout') + 15, (int) env('MAIL_SEND_QUEUE_RETRY_AFTER', 120));
        $this->assertLessThan(config('ai.analysis.job_timeout', 170), config('gmail.queue.timeout'));
    }
}
