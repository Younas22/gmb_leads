<?php

namespace App\Http\Middleware;

use App\Services\PlanFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard for boolean plan features: `plan.feature:ai_sales_profile_enabled`.
 * Uses the existing package_features system (with config defaults).
 */
class EnsurePlanFeature
{
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        if (!$user || !PlanFeatures::enabled($user, $featureKey)) {
            // A feature the admin locked shows the admin's own message
            $locked = $user ? PlanFeatures::lockMessage($user, $featureKey) : null;

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $locked ?? 'Your current plan does not include this feature.',
                ], 403);
            }

            return redirect()->route('user.subscription')
                ->with('warning', $locked ?? 'Your current plan does not include this feature. Please upgrade to use it.');
        }

        return $next($request);
    }
}
