<?php

namespace Tests\Feature\Ai;

use App\Models\AiUsageLog;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Providers\GeminiAiClient;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Security\SecurityTestCase;

/** Admin → AI Setup: Gemini (Google) provider, generateContent client and free-tier usage panel. */
class GeminiAiSetupTest extends SecurityTestCase
{
    private const GEMINI_KEY = 'AQ.test-gemini-KEY-efghQRST';

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
            'gemini' => ['model' => '', 'effort' => '', 'max_tokens' => '', 'free_rpm' => '', 'free_rpd' => ''],
        ], $payload));
    }

    private function geminiOk(array $extra = []): array
    {
        return array_merge([
            'candidates' => [[
                'content' => ['role' => 'model', 'parts' => [['text' => 'thinking...', 'thought' => true], ['text' => '{"ok":true}']]],
                'finishReason' => 'STOP',
            ]],
            'usageMetadata' => ['promptTokenCount' => 100, 'candidatesTokenCount' => 10, 'thoughtsTokenCount' => 40, 'cachedContentTokenCount' => 5],
            'modelVersion' => 'gemini-3.5-flash-lite',
        ], $extra);
    }

    public function test_gemini_can_be_activated_and_then_handles_all_ai_requests(): void
    {
        $this->save(['active_provider' => 'gemini', 'gemini' => ['api_key' => self::GEMINI_KEY, 'model' => 'gemini-3.5-flash-lite', 'effort' => 'low', 'free_rpm' => '15', 'free_rpd' => '500']])
            ->assertRedirect('/admin/ai-setup')->assertSessionHas('success');

        $this->assertStringNotContainsString('efghQRST', (string) DB::table('settings')->where('key', 'ai_gemini_api_key')->value('value'));
        $client = $this->client();
        $this->assertInstanceOf(GeminiAiClient::class, $client);
        $this->assertSame('gemini-3.5-flash-lite', $client->model());
        $this->assertSame(['rpm' => 15, 'rpd' => 500, 'source' => 'admin'], app(AiSettings::class)->geminiFreeTier());

        $this->actingAs($this->makeAdmin())->get('/admin/ai-setup')->assertOk()
            ->assertSee('Gemini (Google)')->assertSee('Free tier: limits')
            ->assertDontSee(self::GEMINI_KEY)->assertSee('QRST');
    }

    public function test_gemini_without_key_cannot_be_activated(): void
    {
        config(['ai.providers.gemini.api_key' => null]);
        $this->save(['active_provider' => 'gemini'])->assertSessionHasErrors('active_provider');
    }

    public function test_free_tier_panel_counts_todays_requests_against_the_limit(): void
    {
        $this->save(['gemini' => ['api_key' => self::GEMINI_KEY, 'model' => 'gemini-3.8-flash']]);
        $this->assertSame(['rpm' => 5, 'rpd' => 20, 'source' => 'default'], app(AiSettings::class)->geminiFreeTier());

        $user = $this->makeUser();
        $row = ['owner_user_id' => $user->id, 'user_id' => $user->id, 'feature' => 'email_draft', 'provider' => 'gemini', 'model' => 'gemini-3.8-flash'];
        AiUsageLog::create($row + ['status' => AiUsageLog::STATUS_SUCCESS, 'input_tokens' => 1000, 'output_tokens' => 200]);
        AiUsageLog::create($row + ['status' => AiUsageLog::STATUS_FAILED, 'error_type' => 'billing']);
        AiUsageLog::create($row + ['status' => AiUsageLog::STATUS_REJECTED, 'error_type' => 'quota_exceeded']); // never sent to Google
        AiUsageLog::create(['provider' => 'openai', 'status' => AiUsageLog::STATUS_SUCCESS] + $row);

        $this->actingAs($this->makeAdmin())->get('/admin/ai-setup')->assertOk()
            ->assertSee('18 left today')
            ->assertSee('1,200')
            ->assertSee('prepayment credits depleted');
    }

    public function test_client_sends_a_generate_content_request_and_parses_the_answer(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiOk())]);
        $client = new GeminiAiClient(['api_key' => self::GEMINI_KEY, 'model' => 'gemini-3.5-flash-lite', 'max_tokens' => 9000]);
        $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['ok'], 'properties' => ['ok' => ['type' => 'boolean']]];

        $res = $client->complete(new AiRequest('You are helpful.', [
            ['role' => 'user', 'content' => 'Hi'], ['role' => 'assistant', 'content' => 'Yo'], ['role' => 'user', 'content' => 'Again'],
        ], 4000, 'max', $schema));

        $this->assertSame('{"ok":true}', $res->text, 'Thought parts are skipped');
        $this->assertSame([100, 50, 5], [$res->inputTokens, $res->outputTokens, $res->cacheReadTokens]);
        $this->assertFalse($res->wasTruncated());
        Http::assertSent(function (HttpRequest $r) use ($schema) {
            $b = $r->data();

            return $r->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent'
                && $r->hasHeader('x-goog-api-key', self::GEMINI_KEY)
                && $b['systemInstruction'] === ['parts' => [['text' => 'You are helpful.']]]
                && array_column($b['contents'], 'role') === ['user', 'model', 'user']
                && $b['contents'][1]['parts'] === [['text' => 'Yo']]
                && $b['generationConfig']['maxOutputTokens'] === 4000
                && $b['generationConfig']['thinkingConfig'] === ['thinkingLevel' => 'high']
                && $b['generationConfig']['responseMimeType'] === 'application/json'
                && $b['generationConfig']['responseJsonSchema'] === $schema;
        });
    }

    public function test_truncation_blocks_and_errors_map_to_the_shared_contract(): void
    {
        $client = new GeminiAiClient(['api_key' => self::GEMINI_KEY, 'model' => 'gemini-3.5-flash-lite']);
        $req = AiRequest::prompt('s', 'u');

        $cases = [
            [['promptFeedback' => ['blockReason' => 'SAFETY']], 200, AiProviderException::REFUSAL],
            [['candidates' => [['finishReason' => 'SAFETY', 'content' => ['parts' => []]]]], 200, AiProviderException::REFUSAL],
            [['error' => ['code' => 402, 'message' => 'Your prepayment credits are depleted.', 'status' => 'RESOURCE_EXHAUSTED']], 402, AiProviderException::BILLING],
            [['error' => ['code' => 400, 'message' => 'API key not valid. Please pass a valid API key.', 'status' => 'INVALID_ARGUMENT']], 400, AiProviderException::AUTHENTICATION],
            [['error' => ['code' => 429, 'message' => 'Quota exceeded', 'status' => 'RESOURCE_EXHAUSTED']], 429, AiProviderException::RATE_LIMITED],
            [['error' => ['code' => 404, 'message' => 'model not found', 'status' => 'NOT_FOUND']], 404, AiProviderException::BAD_REQUEST],
            [['error' => ['code' => 503, 'message' => 'overloaded', 'status' => 'UNAVAILABLE']], 503, AiProviderException::OVERLOADED],
        ];
        $seq = Http::sequence()->push($this->geminiOk(['candidates' => [['content' => ['parts' => [['text' => '{"ok":']]], 'finishReason' => 'MAX_TOKENS']]]));
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
        $this->assertFalse((new AiProviderException(AiProviderException::BILLING))->isRetryable(), 'No credit is not retried');
    }

    public function test_admin_thinking_level_token_cap_and_connection_test(): void
    {
        $this->save(['gemini' => ['api_key' => self::GEMINI_KEY, 'model' => 'gemini-4-hydrogen', 'effort' => 'xhigh', 'max_tokens' => '2000']]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiOk())]);

        (new GeminiAiClient(app(AiSettings::class)->providerConfig('gemini')))->complete(AiRequest::prompt('s', 'u', 16000, 'low'));
        Http::assertSent(fn (HttpRequest $r) => $r->data()['generationConfig']['maxOutputTokens'] === 2000
            && $r->data()['generationConfig']['thinkingConfig'] === ['thinkingLevel' => 'xhigh']
            && str_contains($r->url(), '/models/gemini-4-hydrogen:generateContent'));

        $this->actingAs($this->makeAdmin())->postJson('/admin/ai-setup/test/gemini')->assertOk()->assertJson(['ok' => true]);
        $this->actingAs($this->makeUser())->postJson('/admin/ai-setup/test/gemini')->assertForbidden();
    }
}
