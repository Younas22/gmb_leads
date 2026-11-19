<?php

namespace Tests\Feature\AiOutreach;

use App\Services\Web\FetchException;
use App\Services\Web\IpGuard;
use App\Services\Web\RobotsTxt;
use App\Services\Web\SafeHttpFetcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

class SafeFetchingTest extends OutreachTestCase
{
    private function fetcher(): SafeHttpFetcher
    {
        return $this->app->make(SafeHttpFetcher::class);
    }

    private function assertRefused(string $url, string $reason): void
    {
        try {
            $this->fetcher()->get($url);
            $this->fail("Expected {$url} to be refused");
        } catch (FetchException $e) {
            $this->assertSame($reason, $e->reason, "Wrong reason for {$url}");
        }
    }

    // ── IP classification ────────────────────────────────────────────────

    public static function blockedIps(): array
    {
        return array_map(fn ($ip) => [$ip], [
            '127.0.0.1', '10.1.2.3', '172.16.5.4', '192.168.1.1', '169.254.169.254', '100.64.0.1', '0.0.0.0',
            '192.0.2.10', '198.18.0.1', '224.0.0.1', '255.255.255.255', '::1', 'fc00::1', 'fd12:3456::1',
            'fe80::1', '::ffff:10.0.0.1', '::ffff:127.0.0.1', '64:ff9b::a00:1', '2001:db8::1', 'not-an-ip',
        ]);
    }

    #[DataProvider('blockedIps')]
    public function test_non_public_addresses_are_blocked(string $ip): void
    {
        $this->assertFalse(IpGuard::isPublic($ip));
    }

    public function test_public_addresses_are_allowed(): void
    {
        foreach (['93.184.216.34', '8.8.8.8', '1.1.1.1', '2606:4700:4700::1111', '2001:4860:4860::8888'] as $ip) {
            $this->assertTrue(IpGuard::isPublic($ip), $ip);
        }
    }

    // ── URL / DNS validation ─────────────────────────────────────────────

    public function test_unsafe_urls_are_refused_before_any_request(): void
    {
        Http::fake();
        $this->dns['internal.example'] = ['10.0.0.5'];
        $this->dns['mixed.example'] = ['93.184.216.34', '192.168.0.10'];
        $this->dns['public.example'] = ['93.184.216.34'];

        $this->assertRefused('file:///etc/passwd', FetchException::INVALID_URL);
        $this->assertRefused('javascript:alert(1)', FetchException::INVALID_URL);
        $this->assertRefused('ftp://public.example/', FetchException::INVALID_URL);
        $this->assertRefused('http://127.0.0.1/', FetchException::PRIVATE_ADDRESS);
        $this->assertRefused('http://169.254.169.254/latest/meta-data/', FetchException::PRIVATE_ADDRESS);
        $this->assertRefused('http://[::1]/', FetchException::PRIVATE_ADDRESS);
        $this->assertRefused('http://localhost/', FetchException::BLOCKED_HOST);
        $this->assertRefused('http://printer.local/', FetchException::BLOCKED_HOST);
        $this->assertRefused('http://intranet/', FetchException::BLOCKED_HOST);
        $this->assertRefused('https://internal.example/', FetchException::PRIVATE_ADDRESS);
        $this->assertRefused('https://mixed.example/', FetchException::PRIVATE_ADDRESS);
        $this->assertRefused('https://unknown.example/', FetchException::DNS_FAILED);
        $this->assertRefused('https://public.example:8080/', FetchException::BAD_PORT);
        $this->assertRefused('https://user:pass@public.example/', FetchException::INVALID_URL);

        Http::assertNothingSent();
    }

    public function test_connection_is_pinned_to_the_validated_address(): void
    {
        $this->dns['public.example'] = ['93.184.216.34'];

        [$host, $port, $ip] = $this->fetcher()->validateTarget('https://public.example/page');

        $this->assertSame(['public.example', 443, '93.184.216.34'], [$host, $port, $ip]);
    }

    // ── Redirects ────────────────────────────────────────────────────────

    public function test_redirect_to_a_private_address_is_blocked_at_the_hop(): void
    {
        $this->dns['public.example'] = ['93.184.216.34'];
        Http::fake([
            'https://public.example/' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data/']),
        ]);

        $this->assertRefused('https://public.example/', FetchException::PRIVATE_ADDRESS);
        Http::assertSentCount(1);
    }

    public function test_redirect_to_internal_hostname_is_blocked(): void
    {
        $this->dns['public.example'] = ['93.184.216.34'];
        $this->dns['evil-rebind.example'] = ['127.0.0.1'];
        Http::fake([
            'https://public.example/' => Http::response('', 301, ['Location' => 'https://evil-rebind.example/admin']),
        ]);

        $this->assertRefused('https://public.example/', FetchException::PRIVATE_ADDRESS);
    }

    public function test_safe_redirects_are_followed_and_logged_without_query_strings(): void
    {
        $this->dns['public.example'] = ['93.184.216.34'];
        $this->dns['www.public.example'] = ['93.184.216.35'];
        Http::fake([
            'https://public.example/' => Http::response('', 301, ['Location' => 'https://www.public.example/home?token=secret']),
            'https://www.public.example/home*' => Http::response('<html><title>Hi</title></html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $result = $this->fetcher()->get('https://public.example/');

        $this->assertSame(200, $result->status);
        $this->assertSame('https://www.public.example/home?token=secret', $result->finalUrl);
        $this->assertSame(['https://public.example/', 'https://www.public.example/home'], array_column($result->hops, 'url'));
        $this->assertStringNotContainsString('secret', json_encode($result->hops));
    }

    public function test_too_many_redirects(): void
    {
        $this->dns['loop.example'] = ['93.184.216.34'];
        Http::fake(['https://loop.example/*' => Http::response('', 302, ['Location' => 'https://loop.example/again'])]);

        $this->assertRefused('https://loop.example/start', FetchException::TOO_MANY_REDIRECTS);
    }

    // ── Response limits ──────────────────────────────────────────────────

    public function test_large_responses_are_truncated_to_the_limit(): void
    {
        config(['ai.fetcher.max_bytes' => 1000]);
        $this->dns['big.example'] = ['93.184.216.34'];
        Http::fake(['https://big.example/' => Http::response(str_repeat('a', 50_000), 200, ['Content-Type' => 'text/html'])]);

        $result = $this->fetcher()->get('https://big.example/');

        $this->assertTrue($result->truncated);
        $this->assertSame(1000, strlen($result->body));
    }

    public function test_non_html_content_is_rejected(): void
    {
        $this->dns['img.example'] = ['93.184.216.34'];
        Http::fake(['https://img.example/' => Http::response('PNG', 200, ['Content-Type' => 'image/png'])]);

        $this->assertRefused('https://img.example/', FetchException::UNSUPPORTED_CONTENT);
    }

    public function test_timeouts_are_reported(): void
    {
        $this->dns['slow.example'] = ['93.184.216.34'];
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 10001 milliseconds'));

        $this->assertRefused('https://slow.example/', FetchException::TIMEOUT);
    }

    public function test_per_host_rate_limit(): void
    {
        config(['ai.fetcher.per_host_per_minute' => 2]);
        $this->dns['busy.example'] = ['93.184.216.34'];
        // A fresh response per request (like real HTTP)
        Http::fake(fn () => Http::response('<html></html>', 200, ['Content-Type' => 'text/html']));

        $this->fetcher()->get('https://busy.example/a');
        $this->fetcher()->get('https://busy.example/b');
        $this->assertRefused('https://busy.example/c', FetchException::RATE_LIMITED);
    }

    public function test_overall_deadline_is_enforced(): void
    {
        $this->dns['public.example'] = ['93.184.216.34'];
        Http::fake();

        try {
            $this->fetcher()->get('https://public.example/', deadline: microtime(true) - 1);
            $this->fail('Expected budget exception');
        } catch (FetchException $e) {
            $this->assertSame(FetchException::BUDGET_EXCEEDED, $e->reason);
        }
        Http::assertNothingSent();
    }

    // ── robots.txt ───────────────────────────────────────────────────────

    public function test_robots_rules(): void
    {
        $rules = ['mode' => 'rules', 'rules' => RobotsTxt::parse("User-agent: *\nDisallow: /private\nAllow: /private/ok\n\nUser-agent: OtherBot\nDisallow: /")];

        $this->assertTrue(RobotsTxt::matches($rules, '/'));
        $this->assertFalse(RobotsTxt::matches($rules, '/private/area'));
        $this->assertTrue(RobotsTxt::matches($rules, '/private/ok/page'));

        $ours = ['mode' => 'rules', 'rules' => RobotsTxt::parse("User-agent: *\nAllow: /\n\nUser-agent: CustomerNearMeBot\nDisallow: /")];
        $this->assertFalse(RobotsTxt::matches($ours, '/anything'));

        $wild = ['mode' => 'rules', 'rules' => RobotsTxt::parse("User-agent: *\nDisallow: /*.pdf$")];
        $this->assertFalse(RobotsTxt::matches($wild, '/files/a.pdf'));
        $this->assertTrue(RobotsTxt::matches($wild, '/files/a.pdf.html'));

        $this->assertFalse(RobotsTxt::matches(['mode' => 'disallow_all'], '/'));
        $this->assertTrue(RobotsTxt::matches(['mode' => 'allow_all'], '/'));
    }

    public function test_missing_robots_allows_and_server_error_disallows(): void
    {
        $this->dns['none.example'] = ['93.184.216.34'];
        $this->dns['down.example'] = ['93.184.216.34'];
        Http::fake([
            'https://none.example/robots.txt' => Http::response('', 404),
            'https://down.example/robots.txt' => Http::response('', 503),
        ]);

        $robots = $this->app->make(RobotsTxt::class);
        $this->assertTrue($robots->isAllowed('https://none.example/'));
        $this->assertFalse($robots->isAllowed('https://down.example/'));
    }
}
