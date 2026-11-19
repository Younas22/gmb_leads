<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Allow the request through only for authenticated admin users.
     *
     * Non-admins always get HTTP 403 (JSON or HTML) — never a redirect — so that
     * admin-only endpoints cannot be reached by guessing URLs.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], 403);
            }

            abort(403, 'Access denied. Admin privileges required.');
        }

        return $next($request);
    }
}
