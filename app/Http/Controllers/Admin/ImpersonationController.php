<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * "Login as user" for admins — lets support open a user's account without ever
 * knowing or storing their password (readable passwords were deliberately removed;
 * see security:purge-plain-passwords). The admin's id is kept in the session so
 * they can switch back with one click.
 */
class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    /** Admin-only (route is inside the admin middleware group). */
    public function start(Request $request, User $user)
    {
        $admin = Auth::user();

        if ($user->id === $admin->id || $user->isAdmin()) {
            return back()->with('error', 'You can only log in as a non-admin user.');
        }

        Log::info('Admin started impersonation', ['admin_id' => $admin->id, 'user_id' => $user->id, 'ip' => $request->ip()]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('admin_preview_user');
        $request->session()->put(self::SESSION_KEY, $admin->id);

        return redirect()->route('user.dashboard');
    }

    /** Available to the impersonated session (not admin-gated — the current user is the impersonated one). */
    public function stop(Request $request)
    {
        $adminId = $request->session()->get(self::SESSION_KEY);
        $admin = $adminId ? User::find($adminId) : null;

        if (!$admin || !$admin->isAdmin()) {
            $request->session()->forget(self::SESSION_KEY);
            return redirect()->route('dashboard');
        }

        Log::info('Admin stopped impersonation', ['admin_id' => $admin->id, 'user_id' => Auth::id()]);

        Auth::login($admin);
        $request->session()->regenerate();
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('admin.users')->with('success', 'You are back in your admin account.');
    }
}
