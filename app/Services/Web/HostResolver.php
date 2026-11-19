<?php

namespace App\Services\Web;

/** Resolves a hostname to IP addresses. Swappable in tests. */
interface HostResolver
{
    /** @return list<string> IPv4/IPv6 addresses; empty when the name does not resolve. */
    public function resolve(string $host): array;
}
