<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SavedLead;
use App\Models\SearchHistory;
use App\Models\WelcomeTutorialTracking;
use App\Models\UserFeedback;
use App\Models\Payment;
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

        // Redirect users without active subscription to subscription page
        if ($user->hasRestrictedAccess()) {
            return redirect()->route('user.subscription');
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

        // Get subscription usage data (same as subscription page)
        $usageData = $this->calculateUsageData($user);

        return view('user.dashboard', compact('user', 'stats', 'recentLeads', 'recentSearches', 'usageData'));
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
     * Calculate usage data based on user's subscription
     */
    private function calculateUsageData($user)
    {
        // Get user's current active or pending subscription
        $currentSubscription = $user->subscriptions()
            ->with(['package.features'])
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('created_at', 'desc')
            ->first();

        // Calculate usage statistics
        $currentMonth = Carbon::now()->startOfMonth();
        $today = Carbon::now()->startOfDay();

        // Monthly leads - count saved leads this month
        $monthlyLeadsUsed = $user->savedLeads()
            ->where('created_at', '>=', $currentMonth)
            ->count();

        // Daily searches - count searches today
        $dailySearchesUsed = $user->searchHistories()
            ->where('created_at', '>=', $today)
            ->count();

        // Get limits from current package or default to 0 (no access without subscription)
        $monthlyLeadsLimit = 0; // Default: no subscription
        $dailySearchesLimit = 0; // Default: no subscription
        $apiKeysLimit = 0; // Default: no subscription

        // Get actual API keys count from database
        $apiKeysUsed = $user->apiKeys()->count();

        if ($currentSubscription && $currentSubscription->package) {
            $features = $currentSubscription->package->features;

            foreach ($features as $feature) {
                if ($feature->feature_key === 'leads_per_month') {
                    $monthlyLeadsLimit = $feature->is_unlimited ? 999999 : (int)$feature->feature_value;
                }
                if ($feature->feature_key === 'gmb_searches') {
                    $dailySearchesLimit = $feature->is_unlimited ? 999999 : (int)$feature->feature_value;
                }
                if ($feature->feature_key === 'api_limit') {
                    $apiKeysLimit = $feature->is_unlimited ? 999999 : (int)$feature->feature_value;
                }
            }
        }

        // Usage data
        return [
            'monthly_leads' => [
                'used' => $monthlyLeadsUsed,
                'limit' => $monthlyLeadsLimit,
                'remaining' => max(0, $monthlyLeadsLimit - $monthlyLeadsUsed),
                'percentage' => $monthlyLeadsLimit > 0 ? round(($monthlyLeadsUsed / $monthlyLeadsLimit) * 100) : 0,
                'is_unlimited' => $monthlyLeadsLimit >= 999999,
            ],
            'daily_searches' => [
                'used' => $dailySearchesUsed,
                'limit' => $dailySearchesLimit,
                'remaining' => max(0, $dailySearchesLimit - $dailySearchesUsed),
                'percentage' => $dailySearchesLimit > 0 ? round(($dailySearchesUsed / $dailySearchesLimit) * 100) : 0,
                'is_unlimited' => $dailySearchesLimit >= 999999,
            ],
            'api_keys' => [
                'used' => $apiKeysUsed,
                'limit' => $apiKeysLimit,
                'percentage' => $apiKeysLimit > 0 ? round(($apiKeysUsed / $apiKeysLimit) * 100) : 0,
                'is_unlimited' => $apiKeysLimit >= 999999,
            ],
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

        // Recent payments (last 10)
        $recentPayments = Payment::with(['user', 'subscription.package', 'paymentMethod'])
            ->latest()
            ->take(10)
            ->get();

        // Payment stats
        $paymentStats = [
            'total_revenue' => Payment::completed()->sum('amount'),
            'pending_payments' => Payment::pending()->count(),
            'this_month' => Payment::completed()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        // Calculate package distribution stats
        $packageStats = $this->calculatePackageDistribution();

        // Calculate search activity stats
        $searchStats = $this->calculateSearchStats();

        return view('admin.dashboard', compact('user', 'recentFeedback', 'recentPayments', 'paymentStats', 'packageStats', 'searchStats'));
    }

    /**
     * Calculate package distribution statistics
     */
    private function calculatePackageDistribution()
    {
        $totalUsers = \App\Models\User::where('user_type', 'user')->count();

        // Get all packages with their subscription counts
        $packages = \App\Models\Package::with(['subscriptions' => function($query) {
            $query->where('status', 'active');
        }])->get();

        $distribution = [];

        foreach ($packages as $package) {
            $subscriberCount = $package->subscriptions->count();
            $percentage = $totalUsers > 0 ? round(($subscriberCount / $totalUsers) * 100, 1) : 0;

            $distribution[] = [
                'name' => $package->name,
                'billing_type' => $package->billing_type,
                'package_for' => $package->package_for,
                'count' => $subscriberCount,
                'percentage' => $percentage,
                'icon' => $this->getPackageIcon($package->name),
                'color' => $this->getPackageColor($package->name)
            ];
        }

        // Calculate users without subscription
        $usersWithSubscription = \App\Models\Subscription::where('status', 'active')
            ->distinct('user_id')
            ->count('user_id');
        $usersWithoutSubscription = $totalUsers - $usersWithSubscription;
        $noSubPercentage = $totalUsers > 0 ? round(($usersWithoutSubscription / $totalUsers) * 100, 1) : 0;

        if ($usersWithoutSubscription > 0) {
            array_unshift($distribution, [
                'name' => 'No Subscription',
                'billing_type' => null,
                'package_for' => null,
                'count' => $usersWithoutSubscription,
                'percentage' => $noSubPercentage,
                'icon' => 'fa-user',
                'color' => 'gray'
            ]);
        }

        return $distribution;
    }

    /**
     * Get icon for package based on name
     */
    private function getPackageIcon($packageName)
    {
        $name = strtolower($packageName);

        if (str_contains($name, 'free') || str_contains($name, 'trial')) {
            return 'fa-gift';
        } elseif (str_contains($name, 'pro') || str_contains($name, 'premium')) {
            return 'fa-crown';
        } elseif (str_contains($name, 'basic') || str_contains($name, 'starter')) {
            return 'fa-star';
        } elseif (str_contains($name, 'enterprise') || str_contains($name, 'business')) {
            return 'fa-building';
        }

        return 'fa-box';
    }

    /**
     * Get color for package based on name
     */
    private function getPackageColor($packageName)
    {
        $name = strtolower($packageName);

        if (str_contains($name, 'free') || str_contains($name, 'trial')) {
            return 'green';
        } elseif (str_contains($name, 'pro') || str_contains($name, 'premium')) {
            return 'primary';
        } elseif (str_contains($name, 'basic') || str_contains($name, 'starter')) {
            return 'orange';
        } elseif (str_contains($name, 'enterprise') || str_contains($name, 'business')) {
            return 'purple';
        }

        return 'blue';
    }

    /**
     * Calculate search activity statistics
     */
    private function calculateSearchStats()
    {
        $today = Carbon::today();

        // Total searches today across all users
        $searchesToday = \App\Models\SearchHistory::whereDate('created_at', $today)->count();

        // Total leads saved today
        $leadsToday = SavedLead::whereDate('created_at', $today)->count();

        // Average searches per user today
        $activeUsersToday = \App\Models\SearchHistory::whereDate('created_at', $today)
            ->distinct('user_id')
            ->count('user_id');

        $avgSearchesPerUser = $activeUsersToday > 0 ? round($searchesToday / $activeUsersToday, 1) : 0;

        return [
            'searches_today' => $searchesToday,
            'leads_today' => $leadsToday,
            'active_users_today' => $activeUsersToday,
            'avg_searches_per_user' => $avgSearchesPerUser,
        ];
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