<?php

namespace App\Services\Ai\Exceptions;

class AiNotConfiguredException extends AiException
{
    public function __construct(string $message = 'AI features are not configured on this server yet.')
    {
        parent::__construct($message);
    }

    public function errorType(): string
    {
        return 'not_configured';
    }
}
