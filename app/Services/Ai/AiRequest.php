<?php

namespace App\Services\Ai;

/**
 * A single text-generation request.
 *
 * @phpstan-type Message array{role: 'user'|'assistant', content: string}
 */
final class AiRequest
{
    /**
     * @param list<array{role: string, content: string}> $messages
     */
    public function __construct(
        public readonly string $system,
        public readonly array $messages,
        public readonly ?int $maxTokens = null,
        public readonly ?string $effort = null,
        /** JSON schema for structured output (object with additionalProperties:false), or null for free text. */
        public readonly ?array $jsonSchema = null,
    ) {
        if ($messages === []) {
            throw new \InvalidArgumentException('An AI request needs at least one message.');
        }
    }

    public static function prompt(string $system, string $userMessage, ?int $maxTokens = null, ?string $effort = null, ?array $jsonSchema = null): self
    {
        return new self($system, [['role' => 'user', 'content' => $userMessage]], $maxTokens, $effort, $jsonSchema);
    }
}
