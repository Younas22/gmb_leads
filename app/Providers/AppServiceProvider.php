<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // DNS resolution for SafeHttpFetcher (swapped for a fake in tests)
        $this->app->bind(\App\Services\Web\HostResolver::class, \App\Services\Web\SystemHostResolver::class);

        // Package feature rows with per-user overrides (admin "Custom features"), read once per request
        $this->app->scoped(\App\Services\FeatureOverrides::class);

        // ONE active AI provider, chosen in Admin → AI Setup (falls back to .env AI_PROVIDER).
        // "off"/"null" (or AI_ENABLED=false) never calls out.
        $this->app->singleton(\App\Services\Ai\Contracts\AiClient::class, function ($app) {
            $settings = $app->make(\App\Services\Ai\AiSettings::class);
            $provider = $settings->activeProvider();

            return match ($provider) {
                'anthropic' => new \App\Services\Ai\Providers\AnthropicAiClient($settings->providerConfig('anthropic')),
                'openai' => new \App\Services\Ai\Providers\OpenAiAiClient($settings->providerConfig('openai')),
                'gemini' => new \App\Services\Ai\Providers\GeminiAiClient($settings->providerConfig('gemini')),
                'openrouter' => new \App\Services\Ai\Providers\OpenRouterAiClient($settings->providerConfig('openrouter')),
                // Offline stand-in for local/staging end-to-end runs; disabled in production
                'fixture' => new \App\Services\Ai\Providers\FixtureAiClient(),
                default => new \App\Services\Ai\Providers\NullAiClient(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // AI Sales Profile: the account owner (individual user or company account)
        // edits it; team members of a company can only view their company's profile.
        \Illuminate\Support\Facades\Gate::define(
            'manage-sales-profile',
            fn (\App\Models\User $user) => !$user->isTeamMember() && !$user->isAdmin()
        );
    }

    /**
     * Named rate limiters used by routes/web.php and routes/api.php.
     *
     * Every 429 response carries both "message" (web forms) and "error"
     * (Chrome extension) so existing clients show a readable error.
     */
    protected function configureRateLimiting(): void
    {
        $tooMany = function (string $message) {
            return function (Request $request, array $headers) use ($message) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error'   => $message,
                ], 429, $headers);
            };
        };

        $loginKey = fn (Request $request) => strtolower(trim((string) $request->input('email'))) . '|' . $request->ip();

        // Web + extension login: per email+IP (password guessing) and per IP (credential stuffing).
        RateLimiter::for('login', function (Request $request) use ($tooMany, $loginKey) {
            $message = 'Too many login attempts. Please wait a minute and try again.';
            return [
                Limit::perMinute(5)->by('login:' . $loginKey($request))->response($tooMany($message)),
                Limit::perMinute(30)->by('login-ip:' . $request->ip())->response($tooMany($message)),
            ];
        });

        // Signup, password reset and verification emails (also limits email abuse).
        RateLimiter::for('auth-sensitive', function (Request $request) use ($tooMany) {
            $message = 'Too many requests. Please wait a few minutes and try again.';
            return [
                Limit::perMinute(5)->by('auth-sensitive:' . $request->ip())->response($tooMany($message)),
                Limit::perHour(30)->by('auth-sensitive-hour:' . $request->ip())->response($tooMany($message)),
            ];
        });

        // Changing the password requires the current one — limit guessing per account.
        RateLimiter::for('password-change', function (Request $request) use ($tooMany) {
            return Limit::perMinute(5)
                ->by('password-change:' . ($request->user()?->id ?? $request->ip()))
                ->response($tooMany('Too many password change attempts. Please wait a minute and try again.'));
        });

        // AI Outreach: starting analyses / regenerating drafts (cost-bearing) and polling.
        RateLimiter::for('ai-outreach-write', function (Request $request) use ($tooMany) {
            return Limit::perMinute(10)->by('ai-write:' . ($request->user()?->id ?? $request->ip()))
                ->response($tooMany('Too many AI requests. Please wait a minute.'));
        });
        RateLimiter::for('ai-outreach-read', function (Request $request) use ($tooMany) {
            return Limit::perMinute(60)->by('ai-read:' . ($request->user()?->id ?? $request->ip()))
                ->response($tooMany('Too many requests. Please slow down.'));
        });

        // Phase 3: confirmed sends / retries (per user) and public unsubscribes (per IP;
        // mail providers' one-click requests can share IPs, so this stays generous).
        RateLimiter::for('email-send', function (Request $request) use ($tooMany) {
            return Limit::perMinute(10)->by('email-send:' . ($request->user()?->id ?? $request->ip()))
                ->response($tooMany('Too many send requests. Please wait a minute.'));
        });
        RateLimiter::for('unsubscribe', fn (Request $request) => Limit::perMinute(120)->by('unsubscribe:' . $request->ip()));
        // Phase 4 tracked-link redirects (public): generous for real people, bounded for floods
        RateLimiter::for('email-link', fn (Request $request) => Limit::perMinute(120)->by('email-link:' . $request->ip()));
        // Phase 5: Resend webhook bursts (per endpoint token)
        RateLimiter::for('resend-webhook', fn (Request $request) => Limit::perMinute(600)->by('resend-webhook:' . (string) $request->route('token')));

        // Authenticated extension calls (status, devices, save-leads). Generous: the
        // extension saves one lead per request while scraping.
        RateLimiter::for('extension-api', function (Request $request) use ($tooMany) {
            $token = (string) $request->bearerToken();
            $key = $token !== '' ? 'ext-token:' . hash('sha256', $token) : 'ext-ip:' . $request->ip();

            return Limit::perMinute(240)->by($key)
                ->response($tooMany('Too many requests from the extension. Please slow down and try again shortly.'));
        });
    }
}
