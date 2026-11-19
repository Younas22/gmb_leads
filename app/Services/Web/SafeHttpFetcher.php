<?php

namespace App\Services\Web;

use App\Support\SafeUrl;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * SSRF-safe HTTP GET for public websites.
 *
 * For every request and every redirect hop:
 *  - only http/https on ports 80/443, no credentials in the URL, no internal hostnames;
 *  - the hostname is resolved and EVERY address must be public (IpGuard);
 *  - the connection is pinned to the validated address (CURLOPT_RESOLVE), so a
 *    second DNS answer cannot redirect it (DNS rebinding), and the address
 *    actually used is checked again after connecting;
 *  - redirects are followed manually (max N) and re-validated hop by hop;
 *  - connect/total timeouts, an overall deadline, a response-size cap and a
 *    content-type allow-list apply; requests per host are rate limited.
 *
 * Logs contain scheme/host/path only: never query strings, headers or bodies.
 */
class SafeHttpFetcher
{
    private const BLOCKED_HOST_SUFFIXES = ['.localhost', '.local', '.internal', '.intranet', '.home.arpa', '.lan', '.corp'];

    public function __construct(private readonly HostResolver $resolver)
    {
    }

    /**
     * @param list<string> $acceptTypes  allowed content-type prefixes for 2xx responses
     * @param float|null   $deadline     absolute microtime(true) after which no request starts
     * @throws FetchException
     */
    public function get(string $url, array $acceptTypes = ['text/html', 'application/xhtml+xml'], ?float $deadline = null, ?int $maxBytes = null): FetchResult
    {
        $config = config('ai.fetcher');
        $maxBytes ??= (int) $config['max_bytes'];
        $maxRedirects = (int) $config['max_redirects'];

        $current = SafeUrl::http($url) ?? throw new FetchException(FetchException::INVALID_URL);
        $hops = [];

        for ($hop = 0; $hop <= $maxRedirects; $hop++) {
            [$host, $port, $ip] = $this->validateTarget($current);
            $this->throttle($host);

            $timeout = (float) $config['timeout'];
            if ($deadline !== null) {
                $remaining = $deadline - microtime(true);
                if ($remaining < 1) {
                    throw new FetchException(FetchException::BUDGET_EXCEEDED);
                }
                $timeout = min($timeout, $remaining);
            }

            $started = microtime(true);
            $connectedIp = null;

            try {
                $response = Http::withHeaders([
                    'User-Agent' => $config['user_agent'],
                    'Accept' => 'text/html,application/xhtml+xml;q=0.9,text/plain;q=0.5',
                    'Accept-Language' => 'en;q=0.9,*;q=0.5',
                ])->withOptions([
                    'allow_redirects' => false,
                    'connect_timeout' => (float) $config['connect_timeout'],
                    'timeout' => $timeout,
                    'stream' => true,
                    'http_errors' => false,
                    'proxy' => '',
                    'verify' => true,
                    'curl' => [
                        CURLOPT_RESOLVE => [$host . ':' . $port . ':' . (str_contains($ip, ':') ? '[' . $ip . ']' : $ip)],
                        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                    ],
                    'on_stats' => function (TransferStats $stats) use (&$connectedIp) {
                        $connectedIp = $stats->getHandlerStat('primary_ip');
                    },
                ])->get($current);
            } catch (ConnectionException $e) {
                $this->log($current, null, $started);
                throw new FetchException(
                    str_contains(strtolower($e->getMessage()), 'timed out') ? FetchException::TIMEOUT : FetchException::CONNECTION_FAILED,
                    $e
                );
            }

            // Defence in depth: the socket must have gone to the address we validated.
            if ($connectedIp !== null && $connectedIp !== '' && trim($connectedIp, '[]') !== $ip) {
                throw new FetchException(FetchException::ADDRESS_MISMATCH);
            }

            $status = $response->status();
            $hops[] = ['url' => self::redact($current), 'status' => $status, 'ms' => (int) round((microtime(true) - $started) * 1000)];
            $this->log($current, $status, $started);

            if ($status >= 300 && $status < 400 && $response->header('Location') !== '') {
                $next = (string) UriResolver::resolve(new Uri($current), new Uri($response->header('Location')));
                $current = SafeUrl::http($next) ?? throw new FetchException(FetchException::INVALID_URL);
                continue;
            }

            $contentType = strtolower(trim(explode(';', $response->header('Content-Type'))[0] ?? ''));

            if ($status >= 200 && $status < 300) {
                $allowed = false;
                foreach ($acceptTypes as $type) {
                    if ($contentType === '' || str_starts_with($contentType, $type)) {
                        $allowed = true;
                        break;
                    }
                }
                if (!$allowed) {
                    throw new FetchException(FetchException::UNSUPPORTED_CONTENT);
                }
            }

            [$body, $truncated] = $this->readLimited($response, $maxBytes);

            return new FetchResult($url, $current, $status, $contentType, $body, $truncated, $hops);
        }

        throw new FetchException(FetchException::TOO_MANY_REDIRECTS);
    }

    /**
     * Validate scheme/host/port and resolve the host to a public IP.
     *
     * @return array{0: string, 1: int, 2: string} host, port, validated IP to pin
     * @throws FetchException
     */
    public function validateTarget(string $url): array
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host']) || empty($parts['scheme'])) {
            throw new FetchException(FetchException::INVALID_URL);
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])) {
            throw new FetchException(FetchException::INVALID_URL);
        }

        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        if (!in_array($port, config('ai.fetcher.allowed_ports', [80, 443]), true)) {
            throw new FetchException(FetchException::BAD_PORT);
        }

        $host = strtolower(rtrim(trim($parts['host'], '[]'), '.'));

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!IpGuard::isPublic($host)) {
                throw new FetchException(FetchException::PRIVATE_ADDRESS);
            }
            return [$host, $port, $host];
        }

        if ($host === 'localhost' || !str_contains($host, '.') || $this->hasBlockedSuffix($host)
            || preg_match('/[^a-z0-9.\-]/', $host)) {
            throw new FetchException(FetchException::BLOCKED_HOST);
        }

        $ips = $this->resolver->resolve($host);
        if ($ips === []) {
            throw new FetchException(FetchException::DNS_FAILED);
        }

        // Every answer must be public: a mix of public and private records is refused.
        foreach ($ips as $ip) {
            if (!IpGuard::isPublic($ip)) {
                throw new FetchException(FetchException::PRIVATE_ADDRESS);
            }
        }

        return [$host, $port, $ips[0]];
    }

    /** URL without credentials, query string or fragment — safe for logs. */
    public static function redact(string $url): string
    {
        $p = parse_url($url);
        if ($p === false || empty($p['host'])) {
            return '[invalid]';
        }

        return ($p['scheme'] ?? 'http') . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '') . ($p['path'] ?? '/');
    }

    private function hasBlockedSuffix(string $host): bool
    {
        foreach (self::BLOCKED_HOST_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function throttle(string $host): void
    {
        $allowed = RateLimiter::attempt(
            'website-fetch:' . $host,
            (int) config('ai.fetcher.per_host_per_minute', 12),
            fn () => true,
            60
        );

        if (!$allowed) {
            throw new FetchException(FetchException::RATE_LIMITED);
        }
    }

    /** @return array{0: string, 1: bool} */
    private function readLimited($response, int $maxBytes): array
    {
        $stream = $response->toPsrResponse()->getBody();
        $body = '';

        while (!$stream->eof() && strlen($body) <= $maxBytes) {
            $chunk = $stream->read(8192);
            if ($chunk === '') {
                break;
            }
            $body .= $chunk;
        }

        $truncated = strlen($body) > $maxBytes || !$stream->eof();
        if ($truncated) {
            $body = substr($body, 0, $maxBytes);
        }
        $stream->close();

        return [$body, $truncated];
    }

    private function log(string $url, ?int $status, float $started): void
    {
        Log::debug('website fetch', [
            'url' => self::redact($url),
            'status' => $status,
            'ms' => (int) round((microtime(true) - $started) * 1000),
        ]);
    }
}
