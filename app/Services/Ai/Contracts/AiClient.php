<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;

/**
 * Provider-neutral text-generation client. Application code depends on this
 * interface (resolved from the container), never on a provider SDK directly.
 */
interface AiClient
{
    /** Short provider name stored in ai_usage_logs, e.g. "anthropic". */
    public function name(): string;

    /** Model id requests are sent to (for logging), or null when not configured. */
    public function model(): ?string;

    /** True when credentials are present and the provider can be called. */
    public function isConfigured(): bool;

    /**
     * @throws \App\Services\Ai\Exceptions\AiNotConfiguredException
     * @throws \App\Services\Ai\Exceptions\AiProviderException
     */
    public function complete(AiRequest $request): AiResponse;
}
