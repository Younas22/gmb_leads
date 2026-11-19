<?php

namespace Tests\Feature\Security;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Finder\Finder;

class SecretsConfigurationTest extends SecurityTestCase
{
    /** Patterns that look like real credentials (not short UI placeholders). */
    private const CREDENTIAL_PATTERNS = [
        'Google API key'      => '/AIza[0-9A-Za-z_\-]{35}/',
        'Resend API key'      => '/re_[0-9A-Za-z]{8}_[0-9A-Za-z]{16,}/',
        'Stripe secret key'   => '/sk_(live|test)_[0-9A-Za-z]{16,}/',
        'Google OAuth secret' => '/GOCSPX-[0-9A-Za-z_\-]{28}/',
        'api_key in URL'      => '/api_key=[0-9A-Za-z]{24,}/',
    ];

    public function test_no_hardcoded_credentials_in_application_source(): void
    {
        $finder = (new Finder())
            ->files()
            ->in([app_path(), config_path(), base_path('routes'), resource_path('views'), base_path('bootstrap')])
            ->name(['*.php']);

        $hits = [];
        foreach ($finder as $file) {
            $contents = $file->getContents();
            foreach (self::CREDENTIAL_PATTERNS as $label => $pattern) {
                if (preg_match($pattern, $contents)) {
                    // Report file + type only, never the value
                    $hits[] = "{$label} in " . $file->getRelativePathname();
                }
            }
        }

        $this->assertSame([], $hits, 'Hardcoded credentials found: ' . implode(', ', $hits));
    }

    public function test_pagespeed_check_uses_configured_key(): void
    {
        config(['services.google_pagespeed.key' => 'configured-test-key']);
        Http::fake(['www.googleapis.com/*' => Http::response([
            'lighthouseResult' => ['categories' => ['performance' => ['score' => 0.42]]],
        ])]);

        $user = $this->makeSubscribedUser();
        $lead = $this->makeLead($user, ['website' => 'https://example.test']);

        $this->actingAs($user)->postJson("/user/leads/{$lead->id}/check-seo")
            ->assertOk()
            ->assertJsonPath('score', 42);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'key=configured-test-key'));
    }

    public function test_pagespeed_check_sends_no_key_when_not_configured(): void
    {
        config(['services.google_pagespeed.key' => null]);
        Http::fake(['www.googleapis.com/*' => Http::response([
            'lighthouseResult' => ['categories' => ['performance' => ['score' => 0.9]]],
        ])]);

        $user = $this->makeSubscribedUser();
        $lead = $this->makeLead($user, ['website' => 'https://example.test']);

        $this->actingAs($user)->postJson("/user/leads/{$lead->id}/check-seo")->assertOk();

        Http::assertSent(fn (HttpRequest $request) => !str_contains($request->url(), 'key='));
    }
}
