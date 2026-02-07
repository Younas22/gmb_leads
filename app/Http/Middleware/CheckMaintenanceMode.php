<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        if (Setting::get('maintenance_mode', false)) {
            // Allow admin users to access normally
            if (Auth::check() && Auth::user()->isAdmin()) {
                return $next($request);
            }

            // Allow login/auth routes so admin can still log in
            if ($request->routeIs('auth.*') || $request->routeIs('login') || $request->routeIs('password.*')) {
                return $next($request);
            }

            return response()->view('maintenance', [
                'siteName' => Setting::get('site_name', config('app.name')),
            ], 503);
        }

        return $next($request);
    }
}
