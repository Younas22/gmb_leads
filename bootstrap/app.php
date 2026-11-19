<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        // RFC 8058 one-click unsubscribe: mail providers POST without a CSRF token.
        // The route is protected by a signed URL instead.
        // Phase 5: Resend webhooks are authenticated by their Svix signature instead.
        $middleware->validateCsrfTokens(except: ['email/unsubscribe/*', 'webhooks/resend/*']);

        $middleware->alias([
            'subscription.access' => \App\Http\Middleware\CheckSubscriptionAccess::class,
            'profile.complete' => \App\Http\Middleware\EnsureProfileIsComplete::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'plan.feature' => \App\Http\Middleware\EnsurePlanFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
