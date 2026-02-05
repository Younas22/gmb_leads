<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SavedLead;
use App\Models\WelcomeTutorialTracking;
use App\Models\UserFeedback;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * General dashboard - redirects to appropriate dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        
        return redirect()->route('user.dashboard');
    }

    /**
     * User dashboard with dynamic data
     */
    public function userDashboard()
    {
        $user = Auth::user();

        // Redirect admin users to admin dashboard (unless previewing user view)
        if ($user->isAdmin() && !session('admin_preview_user')) {
            return redirect()->route('admin.dashboard');
        }
        
        // Calculate dynamic stats
        $stats = $this->calculateUserStats($user->id);
        
        // Get recent leads (last 5)
        $recentLeads = SavedLead::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // Get recent unique searches (fixed GROUP BY issue)
        $recentSearches = SavedLead::where('user_id', $user->id)
            ->select('search_query', 'search_location', DB::raw('MAX(created_at) as created_at'))
            ->whereNotNull('search_query')
            ->groupBy('search_query', 'search_location')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        return view('user.dashboard', compact('user', 'stats', 'recentLeads', 'recentSearches'));
    }

    /**
     * Calculate user statistics
     */
    private function calculateUserStats($userId)
    {
        // Get all leads for user
        $totalLeads = SavedLead::where('user_id', $userId)->count();
        
        // Get today's searches - count distinct search queries created today
        $searchesToday = SavedLead::where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->distinct()
            ->count('search_query');
            
        // Get contacted leads
        $contactedLeads = SavedLead::where('user_id', $userId)
            ->whereIn('contact_status', ['contacted', 'responded', 'converted'])
            ->count();
            
        // Get converted leads
        $convertedLeads = SavedLead::where('user_id', $userId)
            ->where('contact_status', 'converted')
            ->count();
            
        // Calculate rates
        $contactRate = $totalLeads > 0 ? round(($contactedLeads / $totalLeads) * 100, 1) : 0;
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;
        
        // Calculate trial days left (assuming 30 day trial from registration)
        $userCreatedAt = Auth::user()->created_at;
        $trialEndDate = Carbon::parse($userCreatedAt)->addDays(30);
        $trialDaysLeft = max(0, $trialEndDate->diffInDays(Carbon::now(), false));
        
        return [
            'total_leads' => $totalLeads,
            'searches_today' => $searchesToday,
            'contacted_leads' => $contactedLeads,
            'converted_leads' => $convertedLeads,
            'contact_rate' => $contactRate,
            'conversion_rate' => $conversionRate,
            'trial_days_left' => (int) $trialDaysLeft
        ];
    }

    /**
     * Admin dashboard
     */
    public function adminDashboard()
    {
        $user = Auth::user();

        // Check if user is admin
        if (!$user->isAdmin()) {
            return redirect()->route('user.dashboard');
        }

        // Clear preview flag when returning to admin
        session()->forget('admin_preview_user');

        // Recent feedback (last 5)
        $recentFeedback = UserFeedback::with('user')->latest()->take(5)->get();

        return view('admin.dashboard', compact('user', 'recentFeedback'));
    }

    /**
     * Switch admin to user view (preview)
     */
    public function switchToUserView()
    {
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('user.dashboard');
        }

        session(['admin_preview_user' => true]);
        return redirect()->route('user.dashboard');
    }

    /**
     * Switch back to admin view
     */
    public function switchToAdminView()
    {
        session()->forget('admin_preview_user');
        return redirect()->route('admin.dashboard');
    }

    /**
     * Admin users page
     */
    public function adminUsers()
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return redirect()->route('user.dashboard');
        }
        
        // Get all users for admin
        $users = \App\Models\User::latest()->paginate(15);
        
        return view('admin.users', compact('user', 'users'));
    }


    /**
     * Mark welcome tutorial as seen
     */
    public function markWelcomeTutorialSeen(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'seen' => 'required|boolean',
            'dont_show_again' => 'boolean'
        ]);

        if ($request->seen || $request->dont_show_again) {
            $user->markWelcomeTutorialAsSeen();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Show user details (API for modal)
     */
    public function showUser(\App\Models\User $user)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'status' => $user->status,
            'login_type' => $user->login_type,
            'email_verified' => $user->email_verified,
            'avatar' => $user->avatar ? asset('public/' . $user->avatar) : null,
            'created_at' => $user->created_at->format('M d, Y h:i A'),
            'last_login' => $user->last_login ? $user->last_login->format('M d, Y h:i A') : 'Never',
            'saved_leads_count' => $user->savedLeads()->count(),
            'search_histories_count' => $user->searchHistories()->count(),
        ]);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, \App\Models\User $user)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'user_type' => 'required|in:user,admin',
            'status' => 'required|in:active,inactive',
        ]);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => trim($request->first_name . ' ' . $request->last_name),
            'email' => $request->email,
            'user_type' => $request->user_type,
            'status' => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'User updated successfully']);
    }

    /**
     * Delete user
     */
    public function deleteUser(\App\Models\User $user)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Prevent deleting self
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'You cannot delete your own account'], 400);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    }

}