<?php

namespace Tests\Feature\AiSalesProfile;

use App\Models\AiUsageLog;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Ai\AiService;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Exceptions\AiNotConfiguredException;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Exceptions\AiQuotaExceededException;
use App\Services\Ai\Providers\AnthropicAiClient;
use App\Services\Ai\Providers\NullAiClient;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Security\SecurityTestCase;

class AiServiceTest extends SecurityTestCase
{
    /** @var array<int, mixed> queue of AiResponse|Throwable */
    private array $queue = [];
    private int $calls = 0;

    private function fakeClient(bool $configured = true): AiClient
    {
        $test = $this;

        $client = new class($configured, $test) implements AiClient {
            public function __construct(private bool $configured, private $test) {}
            public function name(): string { return 'fake'; }
            public function model(): ?string { return 'fake-model'; }
            public function isConfigured(): bool { return $this->configured; }
            public function complete(AiRequest $request): AiResponse { return $this->test->nextFakeResult(); }
        };

        $this->app->instance(AiClient::class, $client);

        return $client;
    }

    public function nextFakeResult(): AiResponse
    {
        $this->calls++;
        $next = array_shift($this->queue) ?? new AiResponse('ok', 'fake-model', 'end_turn', 100, 20);
        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }

    private function ai(): AiService
    {
        return $this->app->make(AiService::class);
    }

    private function request(): AiRequest
    {
        return AiRequest::prompt('SECRET SYSTEM PROMPT', 'SECRET USER CONTENT');
    }

    // ── Availability ─────────────────────────────────────────────────────

    public function test_ai_is_disabled_without_an_api_key_and_never_calls_out(): void
    {
        config(['ai.enabled' => true, 'ai.provider' => 'anthropic', 'ai.providers.anthropic.api_key' => null]);
        $this->app->forgetInstance(AiClient::class);

        $client = $this->app->make(AiClient::class);
        $this->assertInstanceOf(AnthropicAiClient::class, $client);
        $this->assertFalse($this->ai()->isAvailable());

        $user = $this->makeSubscribedUser([], ['ai_email_drafts_monthly' => 'unlimited']);
        $this->expectException(AiNotConfiguredException::class);
        try {
            $this->ai()->run($user, 'email_draft', $this->request());
        } finally {
            $this->assertSame(0, AiUsageLog::count());
        }
    }

    public function test_master_switch_and_null_provider_disable_ai(): void
    {
        config(['ai.enabled' => false, 'ai.providers.anthropic.api_key' => 'k']);
        $this->app->forgetInstance(AiClient::class);
        $this->assertInstanceOf(NullAiClient::class, $this->app->make(AiClient::class));

        config(['ai.enabled' => true, 'ai.provider' => 'null']);
        $this->app->forgetInstance(AiClient::class);
        $this->assertInstanceOf(NullAiClient::class, $this->app->make(AiClient::class));
        $this->assertFalse($this->ai()->isAvailable());
    }

    public function test_an_unconfigured_client_is_reported_as_unavailable(): void
    {
        $this->fakeClient(configured: false);
        $user = $this->makeSubscribedUser([], ['ai_audits_monthly' => 'unlimited']);

        $this->expectException(AiNotConfiguredException::class);
        $this->ai()->run($user, 'website_audit', $this->request());
    }

    // ── Usage logging ────────────────────────────────────────────────────

    public function test_successful_call_is_logged_with_tokens_and_no_content(): void
    {
        $this->fakeClient();
        $this->queue[] = new AiResponse('generated text', 'fake-model-v2', 'end_turn', 321, 54, 7, 3);
        $user = $this->makeSubscribedUser([], ['ai_email_drafts_monthly' => '10']);
        $lead = $this->makeLead($user);

        $response = $this->ai()->run($user, 'email_draft', $this->request(), $lead);

        $this->assertSame('generated text', $response->text);
        $log = AiUsageLog::sole();
        $this->assertSame($user->id, $log->owner_user_id);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('email_draft', $log->feature);
        $this->assertSame('fake', $log->provider);
        $this->assertSame('fake-model-v2', $log->model);
        $this->assertSame(AiUsageLog::STATUS_SUCCESS, $log->status);
        $this->assertSame(321, $log->input_tokens);
        $this->assertSame(54, $log->output_tokens);
        $this->assertSame(7, $log->cache_read_tokens);
        $this->assertSame(3, $log->cache_write_tokens);
        $this->assertSame('saved_leads', $log->subject_type);
        $this->assertSame($lead->id, $log->subject_id);

        // Logs never hold prompts or responses
        $row = json_encode($log->getAttributes());
        $this->assertStringNotContainsString('SECRET', $row);
        $this->assertStringNotContainsString('generated text', $row);
        $this->assertFalse(Schema::hasColumn('ai_usage_logs', 'prompt'));
        $this->assertFalse(Schema::hasColumn('ai_usage_logs', 'response'));
    }

    // ── Quotas ───────────────────────────────────────────────────────────

    public function test_feature_not_in_plan_is_rejected_before_calling_the_provider(): void
    {
        $this->fakeClient();
        $user = $this->makeSubscribedUser(); // no ai_audits_monthly key → default 0

        try {
            $this->ai()->run($user, 'website_audit', $this->request());
            $this->fail('Expected quota exception');
        } catch (AiQuotaExceededException $e) {
            $this->assertSame('feature_not_in_plan', $e->errorType());
            $this->assertSame(0, $e->limit);
        }

        $this->assertSame(0, $this->calls);
        $this->assertSame(AiUsageLog::STATUS_REJECTED, AiUsageLog::sole()->status);
    }

    public function test_monthly_quota_is_enforced(): void
    {
        $this->fakeClient();
        $user = $this->makeSubscribedUser([], ['ai_audits_monthly' => '2']);

        $this->ai()->run($user, 'website_audit', $this->request());
        $this->ai()->run($user, 'website_audit', $this->request());

        try {
            $this->ai()->run($user, 'website_audit', $this->request());
            $this->fail('Expected quota exception');
        } catch (AiQuotaExceededException $e) {
            $this->assertSame('quota_exceeded', $e->errorType());
            $this->assertSame(2, $e->used);
        }

        $this->assertSame(2, $this->calls);
        $this->assertSame(0, $this->ai()->quota()->remaining($user, 'website_audit'));
        // Other features have their own allowance
        $this->assertSame(0, $this->ai()->quota()->limit($user, 'email_draft'));
    }

    public function test_previous_months_do_not_count(): void
    {
        $this->fakeClient();
        $user = $this->makeSubscribedUser([], ['ai_audits_monthly' => '1']);

        AiUsageLog::forceCreate([
            'owner_user_id' => $user->id, 'user_id' => $user->id, 'feature' => 'website_audit',
            'provider' => 'fake', 'status' => 'success', 'created_at' => now()->subMonthNoOverflow()->startOfMonth(),
        ]);

        $this->ai()->run($user, 'website_audit', $this->request());
        $this->assertSame(1, $this->calls);
    }

    public function test_unlimited_plan_has_no_cap(): void
    {
        $this->fakeClient();
        $user = $this->makeSubscribedUser([], ['ai_email_drafts_monthly' => 'unlimited']);

        for ($i = 0; $i < 5; $i++) {
            $this->ai()->run($user, 'email_draft', $this->request());
        }

        $this->assertSame(5, $this->calls);
        $this->assertNull($this->ai()->quota()->remaining($user, 'email_draft'));
    }

    public function test_company_and_team_members_share_one_quota(): void
    {
        $this->fakeClient();
        $company = $this->makeSubscribedUser(['user_type' => 'company'], ['ai_email_drafts_monthly' => '2']);
        $member = $this->makeUser(['company_id' => $company->id]);

        $this->ai()->run($company, 'email_draft', $this->request());
        $this->ai()->run($member, 'email_draft', $this->request());

        $this->expectException(AiQuotaExceededException::class);
        try {
            $this->ai()->run($member, 'email_draft', $this->request());
        } finally {
            $this->assertSame(2, AiUsageLog::where('owner_user_id', $company->id)->where('status', 'success')->count());
            $this->assertSame(1, AiUsageLog::where('user_id', $member->id)->where('status', 'success')->count());
        }
    }

    public function test_unknown_features_are_refused_instead_of_running_unmetered(): void
    {
        $this->fakeClient();
        $user = $this->makeSubscribedUser();

        $this->expectException(\InvalidArgumentException::class);
        $this->ai()->run($user, 'something_new', $this->request());
    }

    // ── Provider failures ────────────────────────────────────────────────

    public function test_provider_failure_is_logged_and_does_not_use_quota(): void
    {
        $this->fakeClient();
        $user = $this->makeSubscribedUser([], ['ai_audits_monthly' => '1']);
        $this->queue[] = new AiProviderException(AiProviderException::RATE_LIMITED);

        try {
            $this->ai()->run($user, 'website_audit', $this->request());
            $this->fail('Expected provider exception');
        } catch (AiProviderException $e) {
            $this->assertTrue($e->isRetryable());
        }

        $failed = AiUsageLog::sole();
        $this->assertSame(AiUsageLog::STATUS_FAILED, $failed->status);
        $this->assertSame('rate_limited', $failed->error_type);

        // The failed call did not consume the single allowed call
        $this->ai()->run($user, 'website_audit', $this->request());
        $this->assertSame(1, AiUsageLog::where('status', 'success')->count());
    }

    public function test_refusal_is_logged_as_refused(): void
    {
        $this->fakeClient();
        $user = $this->makeSubscribedUser([], ['ai_audits_monthly' => '5']);
        $this->queue[] = new AiProviderException(AiProviderException::REFUSAL);

        $this->expectException(AiProviderException::class);
        try {
            $this->ai()->run($user, 'website_audit', $this->request());
        } finally {
            $this->assertSame(AiUsageLog::STATUS_REFUSED, AiUsageLog::sole()->status);
        }
    }

    public function test_unexpected_errors_are_wrapped_in_a_safe_exception(): void
    {
        $this->fakeClient();
        $user = $this->makeSubscribedUser([], ['ai_audits_monthly' => '5']);
        $this->queue[] = new \RuntimeException('raw internal error with secrets');

        try {
            $this->ai()->run($user, 'website_audit', $this->request());
            $this->fail('Expected provider exception');
        } catch (AiProviderException $e) {
            $this->assertSame(AiProviderException::UNKNOWN, $e->errorType());
            $this->assertStringNotContainsString('secrets', $e->getMessage());
        }

        $this->assertSame('exception', AiUsageLog::sole()->error_type);
    }
}
