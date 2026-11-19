<?php

namespace Tests\Feature\Security;

use App\Models\SavedLead;
use App\Services\CurrencyHelper;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Finder\Finder;

class OutboundHttpAndSeoTest extends SecurityTestCase
{
    // ── TLS ──────────────────────────────────────────────────────────────

    public function test_no_code_disables_tls_certificate_verification(): void
    {
        $finder = (new Finder())->files()->in([app_path(), config_path(), base_path('routes')])->name('*.php');

        $hits = [];
        foreach ($finder as $file) {
            $code = $file->getContents();
            if (preg_match("/['\"]verify['\"]\s*=>\s*false|withoutVerifying\(|CURLOPT_SSL_VERIFYPEER\s*=>\s*(false|0)|verify_peer['\"]\s*=>\s*false/", $code)) {
                $hits[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $hits, 'TLS verification disabled in: ' . implode(', ', $hits));
    }

    public function test_search_api_key_is_never_logged(): void
    {
        $code = file_get_contents(app_path('Http/Controllers/SearchController.php'));

        // The live-API branch logs a redacted copy of the params (the mock branch only has a dummy key)
        $this->assertStringNotContainsString("\\Log::info('Request params:', \$params);", $code);
        $this->assertStringContainsString("\$loggableParams = array_merge(\$params, ['key' => '[redacted]']);", $code);
        $this->assertStringContainsString("\\Log::info('Request params:', \$loggableParams);", $code);
    }

    // ── Currency helper (geo + exchange-rate lookups) ────────────────────

    public function test_currency_lookup_skips_private_and_local_ips(): void
    {
        Http::fake();

        $this->assertSame('USD', CurrencyHelper::getVisitorCurrency('127.0.0.1')['code']);
        $this->assertSame('USD', CurrencyHelper::getVisitorCurrency('192.168.1.10')['code']);
        $this->assertSame('USD', CurrencyHelper::getVisitorCurrency('not-an-ip')['code']);

        Http::assertNothingSent();
    }

    public function test_currency_lookup_uses_http_client_with_configured_key(): void
    {
        config(['services.unirate.key' => 'unirate-test-key']);
        Http::fake([
            'reallyfreegeoip.org/*' => Http::response(['country_code' => 'PK']),
            'api.unirateapi.com/*'  => Http::response(['rates' => ['PKR' => 280.5]]),
        ]);

        $currency = CurrencyHelper::getVisitorCurrency('8.8.8.8');

        $this->assertSame('PKR', $currency['code']);
        $this->assertSame(280.5, $currency['rate']);
        Http::assertSent(fn (HttpRequest $r) => str_starts_with($r->url(), 'https://reallyfreegeoip.org/json/8.8.8.8'));
        Http::assertSent(fn (HttpRequest $r) => str_starts_with($r->url(), 'https://api.unirateapi.com/api/rates') && $r['api_key'] === 'unirate-test-key');
    }

    public function test_currency_falls_back_to_usd_when_services_fail(): void
    {
        config(['services.unirate.key' => 'unirate-test-key']);
        Http::fake([
            'reallyfreegeoip.org/*' => Http::response(['country_code' => 'PK']),
            'api.unirateapi.com/*'  => Http::response('server error', 500),
        ]);

        $this->assertSame('USD', CurrencyHelper::getVisitorCurrency('8.8.4.4')['code']);
    }

    // ── SEO score: never invented ────────────────────────────────────────

    public function test_seo_check_failure_stores_unavailable_marker_not_a_random_score(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response(['error' => ['message' => 'Lighthouse failed']], 500)]);

        $user = $this->makeSubscribedUser();
        $lead = $this->makeLead($user, ['website' => 'https://example.test']);

        $this->actingAs($user)->postJson("/user/leads/{$lead->id}/check-seo")
            ->assertOk()
            ->assertJson(['success' => false, 'score' => null, 'status' => 'unavailable']);

        $this->assertSame(SavedLead::SEO_SCORE_UNAVAILABLE, $lead->fresh()->seo_score);
    }

    public function test_seo_check_network_exception_is_reported_as_unavailable(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

        $user = $this->makeSubscribedUser();
        $lead = $this->makeLead($user, ['website' => 'https://example.test']);

        $this->actingAs($user)->postJson("/user/leads/{$lead->id}/check-seo")
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('score', null);

        $this->assertSame(-1, $lead->fresh()->seo_score);
    }

    public function test_unavailable_seo_score_is_retried_and_replaced_by_real_score(): void
    {
        $user = $this->makeSubscribedUser();
        $lead = $this->makeLead($user, ['website' => 'https://example.test', 'seo_score' => -1]);

        Http::fake(['www.googleapis.com/*' => Http::response([
            'lighthouseResult' => ['categories' => ['performance' => ['score' => 0.63]]],
        ])]);

        $this->actingAs($user)->postJson("/user/leads/{$lead->id}/check-seo")
            ->assertOk()
            ->assertJson(['success' => true, 'score' => 63]);

        $this->assertSame(63, $lead->fresh()->seo_score);
    }

    public function test_unavailable_seo_score_is_rendered_as_na_not_as_a_number(): void
    {
        $user = $this->makeSubscribedUser([], ['seo_analytics' => 'true']);
        $this->makeLead($user, ['website' => 'https://example.test', 'seo_score' => -1]);

        $this->actingAs($user)->get('/user/leads')
            ->assertOk()
            ->assertDontSee('SEO -1', false);
    }

    public function test_seo_code_contains_no_random_fallback(): void
    {
        $code = file_get_contents(app_path('Http/Controllers/LeadsController.php'));
        $this->assertDoesNotMatchRegularExpression('/rand\(\s*10\s*,\s*20\s*\)/', $code);
    }
}
