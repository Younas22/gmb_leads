<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureProfileIsComplete
{
    /**
     * Routes a user must still be able to reach even with an incomplete
     * profile, so they can actually add their WhatsApp number and log out.
     */
    protected array $allowedRoutes = [
        'user.profile',
        'user.profile.update',
        'user.avatar.upload',
        'user.password.update',
        'user.preferences.update',
        'user.account.delete',
        'user.data.clear',
        'user.verification.send',
    ];

    /**
     * Force users without a WhatsApp number to complete their profile
     * before accessing the rest of the app.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || $user->isAdmin() || $user->hasCompletedProfile()) {
            return $next($request);
        }

        $currentRoute = $request->route()->getName();

        if (in_array($currentRoute, $this->allowedRoutes)) {
            return $next($request);
        }

        return redirect()->route('user.profile')
            ->with('warning', 'Please add your WhatsApp number to complete your profile before continuing.');
    }
}
