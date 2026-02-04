<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserType
{
    public function handle(Request $request, Closure $next, $userType = null)
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('auth.show');
        }

        $user = Auth::user();

        // Check if user account is active
        if ($user->status !== 'active') {
            Auth::logout();
            return redirect()->route('auth.show')->with('error', 'Your account is inactive.');
        }

        // If specific user type is required
        if ($userType && $user->user_type !== $userType) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            
            // Redirect to appropriate dashboard
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('user.dashboard');
        }

        return $next($request);
    }
}