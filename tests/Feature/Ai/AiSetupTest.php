<?php

namespace Tests\Feature\Ai;

use App\Models\Setting;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Providers\AnthropicAiClient;
use App\Services\Ai\Providers\OpenAiAiClient;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Security\SecurityTestCase;

/** Admin → AI Setup: one active provider, encrypted keys, ChatGPT (OpenAI Responses API) client. */
class AiSetupTest extends SecurityTestCase
{
    private const OPENAI_KEY = 'sk-test-openai-KEY-abcdWXYZ';

    private function client(): AiClient
    {
        app()->forgetInstance(AiClient::class);

        return app(AiClient::class);
    }

    private function save(array $payload)
    {
        return $this->actingAs($this->makeAdmin())->put('/admin/ai-setup', array_replace_recursive([
            'active_provider' => 'off',
            'anthropic' => ['model' => '', 'effort' => '', 'max_tokens' => ''],
            'openai' => ['model' => '', 'effort' => '', 'max_tokens' => ''],
        ], $payload));
    }

    private function openAiOk(array $extra = []): array
    {
        return array_merge([
            'id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-6.1-sol',
            'output' => [
                ['type' => 'reasoning', 'id' => 'rs_1', 'summary' => []],
                ['type' => 'message', 'role' => 'assistant', 'status' => 'completed', 'content' => [['type' => 'output_text', 'text' => '{"ok":true}', 'annotations' => []]]],
            ],
            'usage' => ['input_tokens' => 120, 'output_tokens' => 30, 'input_tokens_details' => ['cached_tokens' => 20]],
        ], $extra);
    }

    public function test_only_admins_can_open_or_change_ai_setup(): void
    {
        $this->actingAs($this->makeUser())->get('/admin/ai-setup')->assertForbidden();
        $this->actingAs($this->makeUser())->put('/admin/ai-setup', ['active_provider' => 'off'])->assertForbidden();
        $this->actingAs($this->makeAdmin())->get('/admin/ai-setup')->assertOk()->assertSee('AI Setup')->assertSee('ChatGPT (OpenAI)')->assertSee('Claude (Anthropic)');
    }

    public function test_without_admin_settings_the_env_provider_is_used(): void
    {
        $this->assertSame('anthropic', app(AiSettings::class)->activeProvider());
        $this->assertInstanceOf(AnthropicAiClient::class, $this->client());
    }

    public function test_openai_key_is_encrypted_never_shown_and_only_one_provider_is_active(): void
    {
        $this->save(['active_provider' => 'openai', 'openai' => ['api_key' => self::OPENAI_KEY, 'model' => 'gpt-6.1-sol', 'effort' => 'low', 'max_tokens' => '8000']])
            ->assertRedirect('/admin/ai-setup')->assertSessionHas('success');

        $raw = DB::table('settings')->where('key', 'ai_openai_api_key')->value('value');
        $this->assertNotSame(self::OPENAI_KEY, $raw);
        $this->assertStringNotContainsString('abcdWXYZ', (string) $raw, 'Stored encrypted');
        $this->assertSame(self::OPENAI_KEY, Setting::get('ai_openai_api_key'));

        $client = $this->client();
        $this->assertInstanceOf(OpenAiAiClient::class, $client);
        $this->assertSame('gpt-6.1-sol', $client->model());

        $page = $this->actingAs($this->makeAdmin())->get('/admin/ai-setup')->assertOk();
        $page->assertDontSee(self::OPENAI_KEY)->assertSee('WXYZ');

        // Switching to Claude deactivates ChatGPT (single setting); a blank key field keeps the saved key
        config(['ai.providers.anthropic.api_key' => 'sk-ant-env-key']);
        $this->save(['active_provider' => 'anthropic'])->assertSessionHas('success');
        $this->assertInstanceOf(AnthropicAiClient::class, $this->client());
        $this->assertSame(self::OPENAI_KEY, Setting::get('ai_openai_api_key'));

        $this->save(['active_provider' => 'off'])->assertSessionHas('success');
        $this->assertFalse($this->client()->isConfigured());
    }

    public function test_a_provider_without_key_cannot_be_activated(): void
    {
        config(['ai.providers.openai.api_key' => null]);
        $this->save(['active_provider' => 'openai'])->assertSessionHasErrors('active_provider');
        $this->assertNotSame('openai', app(AiSettings::class)->activeProvider());
    }

    public function test_removing_the_saved_key_falls_back_to_env(): void
    {
        config(['ai.providers.openai.api_key' => 'sk-env-openai-1234']);
        $this->save(['openai' => ['api_key' => self::OPENAI_KEY]]);
        $this->assertSame('admin', app(AiSettings::class)->keySource('openai'));

        $this->save(['openai' => ['remove_key' => '1']]);
        $this->assertSame('env', app(AiSettings::class)->keySource('openai'));
        $this->assertSame('1234', app(AiSettings::class)->keyLast4('openai'));
    }

    public function test_openai_client_sends_a_responses_api_request_and_parses_the_answer(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->openAiOk())]);
        $client = new OpenAiAiClient(['api_key' => self::OPENAI_KEY, 'model' => 'gpt-6.1-sol', 'max_tokens' => 9000, 'effort' => 'medium']);
        $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['ok'], 'properties' => ['ok' => ['type' => 'boolean']]];

        $res = $client->complete(new AiRequest('You are helpful.', [['role' => 'user', 'content' => 'Hi']], 4000, 'high', $schema));

        $this->assertSame('{"ok":true}', $res->text);
        $this->assertSame([120, 30, 20], [$res->inputTokens, $res->outputTokens, $res->cacheReadTokens]);
        $this->assertFalse($res->wasTruncated());
        Http::assertSent(function (HttpRequest $r) {
            $b = $r->data();
            return $r->url() === 'https://api.openai.com/v1/responses'
                && $r->hasHeader('Authorization', 'Bearer ' . self::OPENAI_KEY)
                && $b['model'] === 'gpt-6.1-sol' && $b['instructions'] === 'You are helpful.'
                && $b['input'] === [['role' => 'user', 'content' => 'Hi']]
                && $b['max_output_tokens'] === 4000 && $b['reasoning'] === ['effort' => 'high'] && $b['store'] === false
                && $b['text']['format']['type'] === 'json_schema' && $b['text']['format']['strict'] === true;
        });
    }

    public function test_openai_truncation_refusal_and_errors_map_to_the_shared_contract(): void
    {
        $client = new OpenAiAiClient(['api_key' => self::OPENAI_KEY, 'model' => 'gpt-6.1-sol']);
        $req = AiRequest::prompt('s', 'u');


        $cases = [
            [['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'refusal', 'refusal' => 'No.']]]]], 200, AiProviderException::REFUSAL],
            [['error' => ['message' => 'bad key', 'code' => 'invalid_api_key']], 401, AiProviderException::AUTHENTICATION],
            [['error' => ['message' => 'no credit', 'code' => 'insufficient_quota']], 429, AiProviderException::BAD_REQUEST],
            [['error' => ['message' => 'slow down', 'code' => 'rate_limit_exceeded']], 429, AiProviderException::RATE_LIMITED],
            [['error' => ['message' => 'boom']], 500, AiProviderException::SERVER_ERROR],
        ];
        $seq = Http::sequence()->push($this->openAiOk(['status' => 'incomplete', 'incomplete_details' => ['reason' => 'max_output_tokens']]));
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
        $this->assertFalse((new AiProviderException(AiProviderException::BAD_REQUEST))->isRetryable(), 'No credit is not retried');
    }

    public function test_admin_max_tokens_caps_feature_requests(): void
    {
        $this->save(['openai' => ['api_key' => self::OPENAI_KEY, 'model' => 'gpt-6.1-sol', 'max_tokens' => '2000']]);
        Http::fake(['api.openai.com/*' => Http::response($this->openAiOk())]);

        (new OpenAiAiClient(app(AiSettings::class)->providerConfig('openai')))->complete(AiRequest::prompt('s', 'u', 16000));
        Http::assertSent(fn (HttpRequest $r) => $r->data()['max_output_tokens'] === 2000);

        // No admin value: the feature's own limit is used unchanged
        $this->assertArrayNotHasKey('max_tokens_cap', app(AiSettings::class)->providerConfig('anthropic'));
    }

    public function test_strict_mode_only_for_fully_required_schemas(): void
    {
        $this->assertTrue(OpenAiAiClient::strictCompatible(['type' => 'object', 'additionalProperties' => false, 'required' => ['a'], 'properties' => ['a' => ['type' => 'array', 'items' => ['type' => 'string']]]]));
        $this->assertFalse(OpenAiAiClient::strictCompatible(['type' => 'object', 'additionalProperties' => false, 'required' => [], 'properties' => ['a' => ['type' => 'string']]]));
        $this->assertFalse(OpenAiAiClient::strictCompatible(['type' => 'object', 'required' => ['a'], 'properties' => ['a' => ['type' => 'string']]]));
    }

    public function test_connection_test_uses_the_saved_settings(): void
    {
        $this->save(['openai' => ['api_key' => self::OPENAI_KEY, 'model' => 'gpt-6-luna']]);
        Http::fake(['api.openai.com/*' => Http::sequence()->push($this->openAiOk(['model' => 'gpt-6-luna']))->push(['error' => ['message' => 'bad']], 401)]);

        $res = $this->actingAs($this->makeAdmin())->postJson('/admin/ai-setup/test/openai')->assertOk()->assertJson(['ok' => true]);
        $this->assertStringContainsString('gpt-6-luna', $res->json('message'));
        Http::assertSent(fn (HttpRequest $r) => $r->data()['model'] === 'gpt-6-luna');

        $this->actingAs($this->makeAdmin())->postJson('/admin/ai-setup/test/openai')->assertOk()->assertJson(['ok' => false, 'error' => 'authentication']);
        $this->actingAs($this->makeUser())->postJson('/admin/ai-setup/test/openai')->assertForbidden();
    }
}
