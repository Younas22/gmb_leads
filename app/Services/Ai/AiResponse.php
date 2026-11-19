<?php

namespace App\Services\Ai;

final class AiResponse
{
    public function __construct(
        public readonly string $text,
        public readonly ?string $model,
        public readonly ?string $stopReason,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $cacheReadTokens = 0,
        public readonly int $cacheWriteTokens = 0,
    ) {
    }

    /** True when the model stopped because it hit max_tokens (output may be cut off). */
    public function wasTruncated(): bool
    {
        return $this->stopReason === 'max_tokens';
    }
}
