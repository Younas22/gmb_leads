<?php

namespace App\Services\Web;

final class FetchResult
{
    /**
     * @param list<array{url: string, status: ?int, ms: int}> $hops  URLs without query strings
     */
    public function __construct(
        public readonly string $requestedUrl,
        public readonly string $finalUrl,
        public readonly int $status,
        public readonly string $contentType,
        public readonly string $body,
        public readonly bool $truncated,
        public readonly array $hops,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function isHtml(): bool
    {
        return str_contains($this->contentType, 'text/html') || str_contains($this->contentType, 'application/xhtml+xml');
    }
}
