<?php

namespace Tests\Feature\AiOutreach;

use App\Jobs\AuditLeadWebsite;
use App\Models\AiEmailDraft;
use App\Models\AiLeadAnalysis;
use App\Models\AiUsageLog;
use App\Services\Ai\AiRequest;
use App\Services\Ai\Providers\FixtureAiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/** Phase 2.1 checks: queue/timeout configuration, recovery and edge cases. */
class ProductionReadinessTest extends OutreachTestCase
{
    public function test_timeouts_retries_and_tokens_are_consistent(): void
    {
        $ai = config('ai');
        // Production default (tests override AI_QUEUE_CONNECTION=sync to run jobs inline)
        $connection = config('queue.connections.ai_database');

        $this->assertSame('database', $connection['driver'], 'AI jobs must use a database queue in production config');
        $this->assertSame($ai['analysis']['queue'], $connection['queue']);

        // A job can make 1 + invalid_output_retries model calls, each up to AI_TIMEOUT
        $worstCase = (1 + $ai['analysis']['invalid_output_retries']) * $ai['providers']['anthropic']['timeout'];
        $this->assertGreaterThan($worstCase, $ai['analysis']['job_timeout']);
        $this->assertGreaterThan($ai['analysis']['job_timeout'], $connection['retry_after'], 'retry_after must exceed the job timeout');

        // No hidden SDK retries that could blow the job timeout
        $this->assertSame(0, $ai['providers']['anthropic']['max_retries']);

        // Thinking counts toward max_tokens on claude-opus-5-5: leave room
        $this->assertGreaterThanOrEqual(16000, $ai['analysis']['max_tokens']['analysis']);
        $this->assertGreaterThanOrEqual(16000, $ai['analysis']['max_tokens']['draft']);

        $job = new AuditLeadWebsite(1);
        $this->assertSame($ai['analysis']['job_timeout'], $job->timeout);
        $this->assertSame(3, $job->tries);
    }

    public function test_ai_status_flags_unsafe_timing_from_env(): void
    {
        config(['ai.providers.anthropic.timeout' => 60, 'ai.providers.anthropic.max_retries' => 2, 'ai.analysis.connection' => 'ai_database']);
        $this->artisan('ai:status')->expectsOutputToContain('UNSAFE timing')->assertSuccessful();

        config(['ai.providers.anthropic.timeout' => 75, 'ai.providers.anthropic.max_retries' => 0]);
        $this->artisan('ai:status')->expectsOutputToContain('Timing OK')->assertSuccessful();
    }

    public function test_env_example_ships_safe_ai_timing(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^AI_MAX_RETRIES=0$/m', $example);
        $this->assertMatchesRegularExpression('/^AI_TIMEOUT=75$/m', $example);
        $this->assertMatchesRegularExpression('/^AI_QUEUE_CONNECTION=ai_database$/m', $example);
        $this->assertMatchesRegularExpression('/^ANTHROPIC_API_KEY=$/m', $example, 'No key value in the example file');
    }

    public function test_invalid_output_retries_can_be_disabled_to_cap_calls(): void
    {
        config(['ai.analysis.invalid_output_retries' => 0]);
        $user = $this->seller();
        $this->offerFor($user);
        $lead = $this->leadFor($user);
        $this->fakeAi(fn () => '{"not":"valid"}');

        $this->actingAs($user)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();

        $this->assertCount(1, $this->aiRequests, 'Exactly one model call when correction retries are off');
        $this->assertSame('invalid_output', AiLeadAnalysis::sole()->error_code);
    }

    public function test_jobs_go_to_the_ai_connection_and_queue(): void
    {
        config(['ai.analysis.connection' => 'ai_database']);
        Queue::fake();
        $user = $this->seller();
        $this->offerFor($user);
        $lead = $this->leadFor($user);

        $this->actingAs($user)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();

        Queue::connection('ai_database')->assertPushedOn('ai', AuditLeadWebsite::class);
    }

    public function test_scheduler_runs_one_bounded_ai_worker(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $worker = $events->first(fn ($e) => str_contains($e->command, 'queue:work') && str_contains($e->command, '--queue=ai'));

        $this->assertNotNull($worker);
        $this->assertStringContainsString('queue:work ' . config('ai.analysis.connection'), $worker->command);
        $this->assertStringContainsString('--queue=ai', $worker->command);
        $this->assertStringContainsString('--stop-when-empty', $worker->command);
        $this->assertStringContainsString('--max-time=50', $worker->command);
        $this->assertStringContainsString('--timeout=170', $worker->command);
        $this->assertSame('* * * * *', $worker->expression);
        $this->assertTrue($worker->withoutOverlapping);
        $this->assertSame(10, $worker->expiresAt);

        $this->assertNotNull($events->first(fn ($e) => str_contains($e->command, 'ai:recover-stuck')));
        $this->assertNotNull($events->first(fn ($e) => str_contains($e->command, 'ai:prune-audits')));
    }

    public function test_stuck_items_are_closed_but_queued_ones_are_left_alone(): void
    {
        $user = $this->seller();
        $stuckLead = $this->leadFor($user);
        $queuedLead = $this->leadFor($user);
        $freshLead = $this->leadFor($user);

        $make = function ($lead, $minutesAgo) use ($user) {
            $a = AiLeadAnalysis::create([
                'owner_user_id' => $user->id, 'saved_lead_id' => $lead->id, 'status' => 'analyzing',
                'active_key' => AiLeadAnalysis::activeKey($user->id, $lead->id),
            ]);
            DB::table('ai_lead_analyses')->where('id', $a->id)->update(['updated_at' => now()->subMinutes($minutesAgo)]);
            return $a;
        };
        $stuck = $make($stuckLead, 45);
        $queued = $make($queuedLead, 45);
        $fresh = $make($freshLead, 5);

        // A real serialized job for $queued sits in the AI queue table
        config(['ai.analysis.connection' => 'ai_database']);
        DB::table('jobs')->insert([
            'queue' => 'ai',
            'payload' => json_encode(['data' => ['command' => serialize(new AuditLeadWebsite($queued->id))]]),
            'attempts' => 0, 'available_at' => time(), 'created_at' => time(),
        ]);

        $this->artisan('ai:recover-stuck')->expectsOutputToContain('Closed 1')->assertSuccessful();

        $this->assertSame('failed', $stuck->fresh()->status);
        $this->assertSame('stuck', $stuck->fresh()->error_code);
        $this->assertNull($stuck->fresh()->active_key, 'The lead can be analysed again');
        $this->assertSame('analyzing', $queued->fresh()->status);
        $this->assertSame('analyzing', $fresh->fresh()->status);
    }

    public function test_no_matching_offer_produces_a_conservative_draft(): void
    {
        $user = $this->seller();
        $offer = $this->offerFor($user);
        $lead = $this->leadFor($user);
        $this->fakeAi($this->happyAi($offer->id, ['personalization_level' => 'generic', 'used_sources' => []], ['recommended_offer_id' => 0, 'match_reason' => '']));

        $this->actingAs($user)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();

        $analysis = AiLeadAnalysis::sole();
        $this->assertSame('completed', $analysis->status);
        $this->assertNull($analysis->recommended_offer_id);
        $this->assertStringContainsString('no specific offer matched', $this->aiRequests[1]->messages[0]['content']);
        $draft = AiEmailDraft::sole();
        $this->assertNull($draft->selected_offer_id);
        $this->assertSame('generic', $draft->personalization_level);
    }

    public function test_offer_id_schema_avoids_nullable_unions(): void
    {
        $schema = \App\Services\Ai\Outreach\LeadOpportunityAnalyzer::schema();

        $this->assertSame(['type' => 'integer'], $schema['properties']['recommended_offer_id']);
        $this->assertStringNotContainsString('"null"', json_encode($schema));
        $this->assertStringNotContainsString('minLength', json_encode($schema));
        $this->assertStringNotContainsString('maximum', json_encode($schema));
    }

    public function test_cost_estimate_includes_every_token_type(): void
    {
        config(['ai.providers.anthropic.model' => 'claude-opus-5-5']);
        $user = $this->seller();
        AiUsageLog::forceCreate([
            'owner_user_id' => $user->id, 'user_id' => $user->id, 'feature' => 'email_draft', 'provider' => 'anthropic',
            'status' => 'success', 'input_tokens' => 1_000_000, 'output_tokens' => 100_000, 'cache_read_tokens' => 1_000_000, 'cache_write_tokens' => 1_000_000,
        ]);

        // 1M*$4 + 0.1M*$20 + 1M*$0.20 + 1M*$5 = $11.20
        $this->actingAs($user)->getJson('/user/ai-outreach/usage')->assertOk()->assertJsonPath('estimated_cost_usd', 11.2);
    }

    public function test_fixture_provider_is_never_usable_in_production(): void
    {
        $client = new FixtureAiClient();
        $this->assertTrue($client->isConfigured());

        $this->app['env'] = 'production';
        $this->assertFalse($client->isConfigured());
        $this->expectException(\App\Services\Ai\Exceptions\AiNotConfiguredException::class);
        $client->complete(AiRequest::prompt('s', 'u'));
    }

    public function test_fixture_provider_output_passes_the_real_validators(): void
    {
        config(['ai.provider' => 'fixture']);
        $this->app->forgetInstance(\App\Services\Ai\Contracts\AiClient::class);
        $user = $this->seller();
        $this->offerFor($user);
        $lead = $this->leadFor($user);

        $this->actingAs($user)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();

        $this->assertSame('completed', AiLeadAnalysis::sole()->status);
        $this->assertSame(['fixture'], AiUsageLog::distinct()->pluck('provider')->all());
        $this->assertSame(1, AiEmailDraft::count());
    }

    public function test_no_email_sending_routes_or_mail_calls_exist(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())->map(fn ($r) => $r->uri())->all();
        foreach ($routes as $uri) {
            $this->assertDoesNotMatchRegularExpression('/ai-outreach.*(send|gmail|campaign|resend)/i', $uri);
        }

        // array_merge, not "+": "+" unions by numeric key and silently skipped most Jobs files.
        // The dedicated, user-approved sending jobs (Phase 3 Gmail, Phase 5 Resend) are the only
        // places allowed to send; AI outreach code may only build previews.
        $sendingJobs = ['SendGmailMessage.php', 'SendResendEmail.php'];
        $files = array_merge(glob(app_path('Services/Ai/Outreach/*.php')), glob(app_path('Jobs/*.php')), glob(app_path('Http/Controllers/AiOutreach/*.php')));
        foreach ($files as $file) {
            if (in_array(basename($file), $sendingJobs, true)) {
                continue;
            }
            $code = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/Mail::|(?<!Outbound)EmailService::|->send\(|Resend::/', $code, basename($file) . ' must not send email');
        }
        $this->assertGreaterThan(10, count($files), 'The scan covers every job, service and controller file');
    }
}
