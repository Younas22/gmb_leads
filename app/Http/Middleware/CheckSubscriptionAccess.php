<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscriptionAccess
{
    /**
     * Handle an incoming request.
     *
     * Restricts access for users with pending paid subscriptions and no active subscriptions.
     * These users can only access the dashboard and subscription pages.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Check if user has restricted access (no active subscription)
        if ($user->hasRestrictedAccess()) {
            // Get the current route name
            $currentRoute = $request->route()->getName();

            // Only allow subscription-related routes
            $allowedRoutes = [
                'user.subscription',
                'user.subscription.upgrade',
                'user.payment.submit',
            ];

            // If already on an allowed route, continue
            if (in_array($currentRoute, $allowedRoutes)) {
                return $next($request);
            }

            // Redirect to subscription page with a message
            return redirect()->route('user.subscription')->with('warning', 'Please activate a subscription package to access all features.');
        }

        return $next($request);
    }
}
