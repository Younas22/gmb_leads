<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Exceptions\AiNotConfiguredException;

/**
 * Offline, deterministic stand-in for a model, for local/staging end-to-end runs
 * of the real queue + worker + website fetching WITHOUT paid API calls.
 *
 * It reads the offer ids and allowed evidence ids from the prompt and returns
 * schema-valid JSON. Text quality says nothing about the real model.
 * Refuses to work in production (AI_PROVIDER=fixture is for testing only).
 */
class FixtureAiClient implements AiClient
{
    public function name(): string
    {
        return 'fixture';
    }

    public function model(): ?string
    {
        return 'fixture-model';
    }

    public function isConfigured(): bool
    {
        return !app()->environment('production');
    }

    public function complete(AiRequest $request): AiResponse
    {
        if (!$this->isConfigured()) {
            throw new AiNotConfiguredException('The fixture AI provider cannot be used in production.');
        }

        $prompt = implode("\n", array_column($request->messages, 'content'));
        preg_match_all('/\[offer_id=(\d+)\]/', $prompt, $offers);
        $refs = [];
        if (preg_match('/Allowed source ids(?: for used_sources)?: ([^\n]+)/', $prompt, $m)) {
            $refs = array_map('trim', explode(',', $m[1]));
        }
        $leadRef = in_array('lead:category', $refs, true) ? 'lead:category' : 'lead:name';
        $finding = collect($refs)->first(fn ($r) => preg_match('/^F\d+$/', $r));

        if (isset($request->jsonSchema['properties']['business_summary'])) {
            $text = json_encode([
                'business_summary' => 'Local business (fixture summary).',
                'likely_needs' => [['need' => 'More enquiries from new customers', 'basis' => array_values(array_filter([$leadRef, $finding]))]],
                'recommended_offer_id' => (int) ($offers[1][0] ?? 0),
                'supporting_offer_ids' => [],
                'match_reason' => 'Fixture match based on the business category.',
                'confidence' => 'low',
                'personalization_points' => [['point' => 'Fixture personalization point', 'source' => $leadRef]],
            ]);
        } else {
            $text = json_encode([
                'subject' => 'Quick idea for your business',
                'greeting' => 'Hi there,',
                'body' => 'This is a fixture draft created without a real AI model, used only to test the workflow end to end. It mentions one service and ends with a simple question. Would a short call next week be useful?',
                'call_to_action' => 'Short call next week',
                'used_sources' => [$leadRef],
                'personalization_level' => 'generic',
            ]);
        }

        // Rough token estimate (≈4 characters per token) so usage/cost paths are exercised
        return new AiResponse($text, 'fixture-model', 'end_turn', (int) ceil((strlen($request->system) + strlen($prompt)) / 4), (int) ceil(strlen($text) / 4));
    }
}
