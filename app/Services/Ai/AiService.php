<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Exceptions\AiException;
use App\Services\Ai\Exceptions\AiNotConfiguredException;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Exceptions\AiQuotaExceededException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single entry point for every AI call in the app:
 * availability check → quota reservation → provider call → usage logging.
 *
 * Usage logs never contain prompts, responses or keys.
 */
class AiService
{
    public function __construct(
        private readonly AiClient $client,
        private readonly AiQuota $quota,
    ) {
    }

    public function isAvailable(): bool
    {
        return (bool) config('ai.enabled') && $this->client->isConfigured();
    }

    public function quota(): AiQuota
    {
        return $this->quota;
    }

    /**
     * @throws AiNotConfiguredException  AI switched off / no API key
     * @throws AiQuotaExceededException  plan does not include it or monthly quota used up
     * @throws AiProviderException       provider error (see isRetryable())
     */
    public function run(User $actor, string $feature, AiRequest $request, ?Model $subject = null): AiResponse
    {
        if (!$this->isAvailable()) {
            throw new AiNotConfiguredException();
        }

        $owner = $actor->getAccountOwner();
        $log = $this->reserve($actor, $owner, $feature, $subject);

        $started = microtime(true);

        try {
            $response = $this->client->complete($request);
        } catch (AiException $e) {
            $this->finish($log, $e instanceof AiProviderException && $e->errorType() === AiProviderException::REFUSAL
                ? AiUsageLog::STATUS_REFUSED
                : AiUsageLog::STATUS_FAILED, $started, errorType: $e->errorType());
            $this->report($e, $owner, $feature);
            throw $e;
        } catch (Throwable $e) {
            $this->finish($log, AiUsageLog::STATUS_FAILED, $started, errorType: 'exception');
            $this->report($e, $owner, $feature);
            throw new AiProviderException(AiProviderException::UNKNOWN, $e);
        }

        $this->finish($log, AiUsageLog::STATUS_SUCCESS, $started, response: $response);

        return $response;
    }

    /**
     * Structured (JSON) call with validation.
     *
     * $validate receives the decoded JSON and must return the cleaned data, or
     * throw \InvalidArgumentException with a short reason. On rejection the
     * model gets its previous answer and the reason, and may try again
     * (config ai.analysis.invalid_output_retries). All attempts count as ONE
     * quota unit; tokens of every attempt are logged.
     *
     * @return array{data: array, response: AiResponse}
     * @throws AiNotConfiguredException|AiQuotaExceededException|AiProviderException
     */
    public function runStructured(User $actor, string $feature, AiRequest $request, callable $validate, ?Model $subject = null, ?int $extraAttempts = null): array
    {
        if ($request->jsonSchema === null) {
            throw new \InvalidArgumentException('runStructured() needs a request with a JSON schema.');
        }
        if (!$this->isAvailable()) {
            throw new AiNotConfiguredException();
        }

        $owner = $actor->getAccountOwner();
        $log = $this->reserve($actor, $owner, $feature, $subject);
        $started = microtime(true);
        $attempts = 1 + max(0, $extraAttempts ?? (int) config('ai.analysis.invalid_output_retries', 1));

        $totals = ['in' => 0, 'out' => 0, 'cr' => 0, 'cw' => 0];
        $messages = $request->messages;
        $lastResponse = null;
        $reason = 'unknown';

        try {
            for ($i = 1; $i <= $attempts; $i++) {
                $response = $this->client->complete(new AiRequest(
                    $request->system, $messages, $request->maxTokens, $request->effort, $request->jsonSchema
                ));
                $lastResponse = $response;
                $totals['in'] += $response->inputTokens;
                $totals['out'] += $response->outputTokens;
                $totals['cr'] += $response->cacheReadTokens;
                $totals['cw'] += $response->cacheWriteTokens;

                try {
                    if ($response->wasTruncated()) {
                        throw new \InvalidArgumentException('The answer was cut off; keep every field shorter.');
                    }
                    $decoded = json_decode($response->text, true, 64, JSON_THROW_ON_ERROR);
                    if (!is_array($decoded)) {
                        throw new \InvalidArgumentException('The answer must be a JSON object.');
                    }
                    $data = $validate($decoded);
                } catch (\JsonException) {
                    $reason = 'The answer was not valid JSON.';
                    $data = null;
                } catch (\InvalidArgumentException $e) {
                    $reason = mb_substr($e->getMessage(), 0, 300);
                    $data = null;
                }

                if ($data !== null) {
                    $this->finish($log, AiUsageLog::STATUS_SUCCESS, $started, response: $this->totalResponse($response, $totals));

                    return ['data' => $data, 'response' => $this->totalResponse($response, $totals)];
                }

                // Ask for a corrected answer (append-only conversation)
                $messages = array_merge($messages, [
                    ['role' => 'assistant', 'content' => $response->text],
                    ['role' => 'user', 'content' => "Your previous answer was rejected: {$reason} Return a corrected JSON answer that follows every rule."],
                ]);
            }
        } catch (AiException $e) {
            $this->finish($log, $e instanceof AiProviderException && $e->errorType() === AiProviderException::REFUSAL
                ? AiUsageLog::STATUS_REFUSED
                : AiUsageLog::STATUS_FAILED, $started, response: $lastResponse ? $this->totalResponse($lastResponse, $totals) : null, errorType: $e->errorType());
            $this->report($e, $owner, $feature);
            throw $e;
        } catch (Throwable $e) {
            $this->finish($log, AiUsageLog::STATUS_FAILED, $started, response: $lastResponse ? $this->totalResponse($lastResponse, $totals) : null, errorType: 'exception');
            $this->report($e, $owner, $feature);
            throw new AiProviderException(AiProviderException::UNKNOWN, $e);
        }

        $e = new AiProviderException(AiProviderException::INVALID_OUTPUT);
        $this->finish($log, AiUsageLog::STATUS_FAILED, $started, response: $lastResponse ? $this->totalResponse($lastResponse, $totals) : null, errorType: AiProviderException::INVALID_OUTPUT);
        Log::warning('AI output rejected', ['owner_user_id' => $owner->id, 'feature' => $feature, 'reason' => $reason]);
        throw $e;
    }

    private function totalResponse(AiResponse $last, array $totals): AiResponse
    {
        return new AiResponse($last->text, $last->model, $last->stopReason, $totals['in'], $totals['out'], $totals['cr'], $totals['cw']);
    }

    /**
     * Check the quota and insert a "pending" row (counted as used) under a short
     * lock, so parallel requests cannot overspend the monthly allowance.
     */
    private function reserve(User $actor, User $owner, string $feature, ?Model $subject): AiUsageLog
    {
        $limit = $this->quota->limit($actor, $feature);

        return Cache::lock("ai-quota:{$owner->id}:{$feature}", 10)->block(5, function () use ($actor, $owner, $feature, $subject, $limit) {
            $used = $this->quota->used($owner, $feature);

            if ($limit !== -1 && $used >= $limit) {
                AiUsageLog::create($this->baseRow($actor, $owner, $feature, $subject) + [
                    'status' => AiUsageLog::STATUS_REJECTED,
                    'error_type' => $limit === 0 ? 'feature_not_in_plan' : 'quota_exceeded',
                ]);

                throw new AiQuotaExceededException($feature, $limit, $used);
            }

            return AiUsageLog::create($this->baseRow($actor, $owner, $feature, $subject) + [
                'status' => AiQuota::PENDING,
            ]);
        });
    }

    private function baseRow(User $actor, User $owner, string $feature, ?Model $subject): array
    {
        return [
            'owner_user_id' => $owner->id,
            'user_id' => $actor->id,
            'feature' => $feature,
            'provider' => $this->client->name(),
            'model' => $this->client->model(),
            'subject_type' => $subject ? $subject->getTable() : null,
            'subject_id' => $subject?->getKey(),
        ];
    }

    private function finish(AiUsageLog $log, string $status, float $started, ?AiResponse $response = null, ?string $errorType = null): void
    {
        $log->forceFill([
            'status' => $status,
            'error_type' => $errorType,
            'model' => $response?->model ?? $log->model,
            'input_tokens' => $response?->inputTokens ?? 0,
            'output_tokens' => $response?->outputTokens ?? 0,
            'cache_read_tokens' => $response?->cacheReadTokens ?? 0,
            'cache_write_tokens' => $response?->cacheWriteTokens ?? 0,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ])->save();
    }

    private function report(Throwable $e, User $owner, string $feature): void
    {
        // Class + type only: provider messages can echo request content.
        Log::warning('AI call failed', [
            'owner_user_id' => $owner->id,
            'feature' => $feature,
            'provider' => $this->client->name(),
            'error_type' => $e instanceof AiException ? $e->errorType() : 'exception',
            'exception' => get_class($e->getPrevious() ?? $e),
        ]);
    }
}
