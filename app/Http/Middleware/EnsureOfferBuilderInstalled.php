<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Code can reach production (auto-deploy from main) before its migration runs.
 * Until the AI Offer Builder tables exist, show a friendly message instead of a 500.
 */
class EnsureOfferBuilderInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Schema::hasTable('builder_offer_generations')) {
            $message = 'The AI Offer Builder is being set up. Please try again in a few minutes.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $message], 503)
                : redirect()->route('dashboard')->with('error', $message);
        }

        return $next($request);
    }
}
