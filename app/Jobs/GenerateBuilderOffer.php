<?php

namespace App\Jobs;

use App\Models\BuilderOffer;
use App\Models\BuilderOfferGeneration;
use App\Models\User;
use App\Services\Ai\Exceptions\AiException;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\OfferBuilder\OfferGenerator;
use App\Services\OfferBuilder\ProfileOfferSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AI Offer Builder: one generation run. Payload is only the generation id.
 * The result is stored on the generation; the offer adopts it only when it has
 * no kept version yet (a regeneration never replaces what the user kept).
 */
class GenerateBuilderOffer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;
    public int $timeout = 170;
    public bool $failOnTimeout = true;

    public function __construct(public readonly int $generationId)
    {
        $this->timeout = (int) config('ai.analysis.job_timeout', 170);
    }

    /** @return array<int> */
    public function backoff(): array
    {
        return [30];
    }

    public function handle(OfferGenerator $generator): void
    {
        $gen = BuilderOfferGeneration::find($this->generationId);
        if (!$gen || !$gen->isOpen()) {
            return; // done, failed or removed: nothing to do (idempotent)
        }

        $offer = BuilderOffer::find($gen->builder_offer_id);
        $user = User::find($gen->user_id);
        if (!$offer || !$user || (int) $offer->user_id !== (int) $user->id) {
            $gen->markFailed('This offer is no longer available.');
            return;
        }

        $gen->forceFill(['status' => BuilderOfferGeneration::STATUS_RUNNING, 'started_at' => $gen->started_at ?? now()])->save();

        try {
            $language = (string) data_get($offer->generation_metadata, 'language', 'English');
            $result = $generator->generate($user, $gen->prompt, $language, $offer);
        } catch (AiProviderException $e) {
            if ($e->isRetryable() && $this->attempts() < $this->tries) {
                $gen->forceFill(['status' => BuilderOfferGeneration::STATUS_QUEUED])->save();
                $this->release(30);
                return;
            }
            $gen->markFailed($e->errorType() === AiProviderException::INVALID_OUTPUT
                ? 'The AI answer was incomplete. Please try again; shortening very long inputs can help.'
                : $e->getMessage());
            return;
        } catch (AiException $e) {
            $gen->markFailed($e->getMessage()); // not configured / quota: user-safe messages
            return;
        } catch (Throwable $e) {
            Log::warning('Offer generation failed', ['generation_id' => $gen->id, 'exception' => get_class($e)]);
            $gen->markFailed('Something went wrong while generating the offer. Please try again.');
            return;
        }

        $gen->forceFill([
            'status' => BuilderOfferGeneration::STATUS_COMPLETED,
            'content' => $result['content'],
            'model' => $result['response']->model,
            'input_tokens' => $result['response']->inputTokens,
            'output_tokens' => $result['response']->outputTokens,
            'completed_at' => now(),
            'error_message' => null,
        ])->save();

        // First result is kept automatically; later ones wait for the user to choose.
        $offer->refresh();
        if ($offer->generated_content === null) {
            $offer->forceFill([
                'generated_content' => $result['content'],
                'current_generation_id' => $gen->id,
                'status' => $offer->status === BuilderOffer::STATUS_ARCHIVED ? BuilderOffer::STATUS_ARCHIVED : BuilderOffer::STATUS_GENERATED,
            ])->save();

            // Show it in the AI Sales Profile "Offers" right away (AI Outreach uses that list)
            try {
                app(ProfileOfferSync::class)->sync($offer);
            } catch (Throwable $e) {
                Log::warning('Offer profile sync failed', ['builder_offer_id' => $offer->id, 'exception' => get_class($e)]);
            }
        }
    }

    public function failed(?Throwable $e): void
    {
        $gen = BuilderOfferGeneration::find($this->generationId);
        if ($gen && $gen->isOpen()) {
            $gen->markFailed('The AI took too long to answer. Please try again.');
        }
    }
}
