<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SavedLead;
use App\Models\WelcomeTutorialTracking;
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
        
        // Redirect admin users to admin dashboard
        if ($user->isAdmin()) {
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
        
        return view('admin.dashboard', compact('user'));
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

}