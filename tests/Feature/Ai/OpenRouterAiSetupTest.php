<?php

namespace Tests\Feature\Ai;

use App\Models\AiUsageLog;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Providers\OpenRouterAiClient;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Security\SecurityTestCase;

/** Admin → AI Setup: OpenRouter provider (free models), live key status, tabs + usage page. */
class OpenRouterAiSetupTest extends SecurityTestCase
{
    private const KEY = 'sk-or-v1-test-KEY-ijklMNOP';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function client(): AiClient
    {
        app()->forgetInstance(AiClient::class);

        return app(AiClient::class);
    }

    private function save(array $payload)
    {
        return $this->actingAs($this->makeAdmin())->put('/admin/ai-setup', array_replace_recursive([
            'active_provider' => 'off',
        ], $payload));
    }

    private function chatOk(array $extra = []): array
    {
        return array_merge([
            'id' => 'gen-1', 'object' => 'chat.completion', 'model' => 'nvidia/nemotron-3-ultra-550b-a55b:free',
            'choices' => [['index' => 0, 'finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => '{"ok":true}', 'refusal' => null]]],
            'usage' => ['prompt_tokens' => 25, 'completion_tokens' => 29, 'prompt_tokens_details' => ['cached_tokens' => 3]],
        ], $extra);
    }

    private function keyInfo(): array
    {
        return ['data' => [
            'usage' => 0, 'usage_daily' => 0, 'usage_monthly' => 0, 'limit' => 100, 'limit_remaining' => 100, 'is_free_tier' => true,
            'free_model_daily_requests' => ['used' => 12, 'limit' => 50, 'remaining' => 38],
        ]];
    }

    public function test_save_and_make_active_routes_all_ai_requests_through_openrouter(): void
    {
        Http::fake(['openrouter.ai/api/v1/key' => Http::response($this->keyInfo())]);

        $this->save(['active_provider' => 'off', 'activate' => 'openrouter', 'tab' => 'openrouter', 'openrouter' => ['api_key' => self::KEY]])
            ->assertRedirect('/admin/ai-setup#openrouter')->assertSessionHas('success');

        $this->assertStringNotContainsString('ijklMNOP', (string) DB::table('settings')->where('key', 'ai_openrouter_api_key')->value('value'));
        $this->assertSame('openrouter', app(AiSettings::class)->activeProvider());
        $client = $this->client();
        $this->assertInstanceOf(OpenRouterAiClient::class, $client);
        $this->assertSame('openrouter/free', $client->model(), 'Free router is the default model');

        // Summary card + tab show the live free allowance; the key is never shown
        $this->actingAs($this->makeAdmin())->get('/admin/ai-setup')->assertOk()
            ->assertSee('OpenRouter free left today')->assertSee('38')
            ->assertSee('Free models: limits')->assertSee('1,000 per day')
            ->assertDontSee(self::KEY)->assertSee('MNOP');
    }

    public function test_client_sends_chat_completions_with_json_schema_and_parses_the_answer(): void
    {
        Http::fake(['openrouter.ai/api/v1/chat/completions' => Http::response($this->chatOk())]);
        $client = new OpenRouterAiClient(['api_key' => self::KEY, 'model' => 'openrouter/free', 'max_tokens' => 9000]);
        $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['ok'], 'properties' => ['ok' => ['type' => 'boolean']]];

        $res = $client->complete(new AiRequest('Sys', [['role' => 'user', 'content' => 'Hi'], ['role' => 'assistant', 'content' => 'Yo'], ['role' => 'user', 'content' => 'Again']], 4000, 'max', $schema));

        $this->assertSame('{"ok":true}', $res->text);
        $this->assertSame('nvidia/nemotron-3-ultra-550b-a55b:free', $res->model, 'Logs the model that answered');
        $this->assertSame([25, 29, 3], [$res->inputTokens, $res->outputTokens, $res->cacheReadTokens]);
        Http::assertSent(function (HttpRequest $r) use ($schema) {
            $b = $r->data();

            return $r->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && $r->hasHeader('Authorization', 'Bearer ' . self::KEY)
                && $b['model'] === 'openrouter/free'
                && array_column($b['messages'], 'role') === ['system', 'user', 'assistant', 'user']
                && $b['max_tokens'] === 4000
                && $b['reasoning']['effort'] === 'xhigh'
                && $b['response_format']['json_schema']['schema'] === $schema
                && $b['response_format']['json_schema']['strict'] === true
                && $b['provider'] === ['require_parameters' => true];
        });
    }

    public function test_errors_and_truncation_map_to_the_shared_contract(): void
    {
        $client = new OpenRouterAiClient(['api_key' => self::KEY, 'model' => 'openrouter/free']);
        $req = AiRequest::prompt('s', 'u');
        $cases = [
            [['error' => ['code' => 401, 'message' => 'No auth']], 401, AiProviderException::AUTHENTICATION],
            [['error' => ['code' => 402, 'message' => 'Insufficient credits']], 402, AiProviderException::BILLING],
            [['error' => ['code' => 429, 'message' => 'Rate limit exceeded: free-models-per-day']], 429, AiProviderException::RATE_LIMITED],
            [['error' => ['code' => 502, 'message' => 'Upstream error']], 200, AiProviderException::OVERLOADED], // error object with HTTP 200
            [['error' => ['code' => 400, 'message' => 'bad model']], 400, AiProviderException::BAD_REQUEST],
            [$this->chatOk(['choices' => [['finish_reason' => 'content_filter', 'message' => ['content' => '']]]]), 200, AiProviderException::REFUSAL],
        ];
        $seq = Http::sequence()->push($this->chatOk(['choices' => [['finish_reason' => 'length', 'message' => ['content' => '{"ok":']]]]));
        foreach ($cases as [$body, $status]) {
            $seq->push($body, $status);
        }
        Http::fake(['*' => $seq]);

        $this->assertTrue($client->complete($req)->wasTruncated());
        foreach ($cases as [$body, $status, $type]) {
            try {
                $client->complete($req);
                $this->fail("Expected {$type}");
            } catch (AiProviderException $e) {
                $this->assertSame($type, $e->errorType());
            }
        }
    }

    public function test_usage_tab_lists_requests_by_feature_and_latest_calls(): void
    {
        $user = $this->makeUser();
        $row = ['owner_user_id' => $user->id, 'user_id' => $user->id, 'provider' => 'openrouter', 'model' => 'google/gemma-4-31b-it:free'];
        AiUsageLog::create($row + ['feature' => 'website_audit', 'status' => AiUsageLog::STATUS_SUCCESS, 'input_tokens' => 1500, 'output_tokens' => 300, 'duration_ms' => 4200]);
        AiUsageLog::create($row + ['feature' => 'email_draft', 'status' => AiUsageLog::STATUS_FAILED, 'error_type' => 'rate_limited']);

        $this->actingAs($this->makeAdmin())->get('/admin/ai-setup')->assertOk()
            ->assertSee('Requests today')
            ->assertSee('By provider &amp; feature', false)
            ->assertSee('website audit')->assertSee('google/gemma-4-31b-it:free')
            ->assertSee('Rate / daily limit (429)')
            ->assertSee($user->name);
    }

    public function test_connection_test_button_works_for_openrouter(): void
    {
        $this->save(['openrouter' => ['api_key' => self::KEY, 'model' => 'google/gemma-4-31b-it:free']]);
        Http::fake(['openrouter.ai/api/v1/chat/completions' => Http::response($this->chatOk(['model' => 'google/gemma-4-31b-it:free']))]);

        $res = $this->actingAs($this->makeAdmin())->postJson('/admin/ai-setup/test/openrouter')->assertOk()->assertJson(['ok' => true]);
        $this->assertStringContainsString('gemma-4-31b-it:free', $res->json('message'));
    }
}
