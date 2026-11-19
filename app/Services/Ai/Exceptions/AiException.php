<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

/** Base class for every AI-layer error. Messages are safe to show to users. */
abstract class AiException extends RuntimeException
{
    /** Machine-readable type stored in ai_usage_logs.error_type. */
    abstract public function errorType(): string;

    /** Whether retrying later may succeed. */
    public function isRetryable(): bool
    {
        return false;
    }
}
