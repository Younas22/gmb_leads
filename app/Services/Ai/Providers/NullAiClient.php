<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Exceptions\AiNotConfiguredException;

/** Used when AI is switched off or no provider is configured. Never calls out. */
class NullAiClient implements AiClient
{
    public function name(): string
    {
        return 'none';
    }

    public function model(): ?string
    {
        return null;
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function complete(AiRequest $request): AiResponse
    {
        throw new AiNotConfiguredException();
    }
}
