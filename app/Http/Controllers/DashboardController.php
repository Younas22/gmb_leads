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

        // Get subscription usage data
        $usageData = $this->calculateUsageData($user);

        // Get current plan with features (same as subscription page)
        $currentPlan = null;
        $activeSubscription = $user->activeSubscription();
        if ($activeSubscription) {
            $currentPlan = [
                'subscription' => $activeSubscription,
                'package'      => $activeSubscription->package,
                'is_active'    => $activeSubscription->isActive(),
                'is_pending'   => $activeSubscription->status === 'pending',
                'end_date'     => $activeSubscription->end_date ? \Carbon\Carbon::parse($activeSubscription->end_date) : null,
            ];
            $currentPlan['package']->load('features');
        }

        return view('user.dashboard', compact('user', 'stats', 'recentLeads', 'recentSearches', 'usageData', 'currentPlan'));
    }

    /**
     * Calculate user statistics
     */
    private function calculateUserStats($userId)
    {
        $user = \App\Models\User::find($userId);

        // Determine the account owner (company or user itself)
        $accountOwner = $user->isTeamMember() ? $user->company : $user;

        // Get all user IDs that should be counted (company + all team members)
        $userIds = [$accountOwner->id];
        if ($accountOwner->isCompany()) {
            $teamMemberIds = $accountOwner->teamMembers()->pluck('id')->toArray();
            $userIds = array_merge($userIds, $teamMemberIds);
        }

        // Get all leads for company + team members
        $totalLeads = SavedLead::whereIn('user_id', $userIds)->count();

        // Get today's searches - count distinct search queries created today
        $searchesToday = SavedLead::whereIn('user_id', $userIds)
            ->whereDate('created_at', Carbon::today())
            ->distinct()
            ->count('search_query');

        // Get contacted leads
        $contactedLeads = SavedLead::whereIn('user_id', $userIds)
            ->whereIn('contact_status', ['contacted', 'responded', 'converted'])
            ->count();

        // Get converted leads
        $convertedLeads = SavedLead::whereIn('user_id', $userIds)
            ->where('contact_status', 'converted')
            ->count();

        // Get pending leads (not contacted yet)
        $pendingLeads = SavedLead::whereIn('user_id', $userIds)
            ->where('contact_status', 'not_contacted')
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
            'pending_leads' => $pendingLeads,
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
        // Get user's active subscription (team members inherit from company)
        $currentSubscription = $user->activeSubscription();

        // Calculate usage statistics
        $currentMonth = Carbon::now()->startOfMonth();
        $today = Carbon::now()->startOfDay();

        // Determine the account owner (company or user itself)
        // If user is a team member, get their company
        // If user is a company, use the user itself
        $accountOwner = $user->isTeamMember() ? $user->company : $user;

        // Get all user IDs that should be counted for this quota
        // (company + all team members)
        $userIds = [$accountOwner->id];
        if ($accountOwner->isCompany()) {
            $teamMemberIds = $accountOwner->teamMembers()->pluck('id')->toArray();
            $userIds = array_merge($userIds, $teamMemberIds);
        }

        // Today's leads used
        $todayLeadsUsed = SavedLead::whereIn('user_id', $userIds)
            ->whereDate('created_at', today())
            ->count();

        // Monthly leads used
        $monthlyLeadsUsed = SavedLead::whereIn('user_id', $userIds)
            ->where('created_at', '>=', $currentMonth)
            ->count();

        // Monthly searches used
        $monthlySearchesUsed = \App\Models\ApiUsage::whereIn('user_id', $userIds)
            ->where('date', '>=', $currentMonth->toDateString())
            ->sum('searches_used');

        // Get limits from current package using correct feature keys
        $dailyLeadsLimit = 0;
        $apiKeysLimit    = 0;
        $hasSubscription = false;

        // Get actual API keys count
        $apiKeysUsed = \App\Models\UserApiKey::whereIn('user_id', $userIds)->count();

        if ($currentSubscription && $currentSubscription->package) {
            $hasSubscription = true;
            foreach ($currentSubscription->package->features as $feature) {
                if ($feature->feature_key === 'daily_leads_limit') {
                    $dailyLeadsLimit = ($feature->is_unlimited || $feature->feature_value === 'unlimited')
                        ? 999999
                        : (int)$feature->feature_value;
                }
                if ($feature->feature_key === 'api_limit') {
                    $apiKeysLimit = $feature->is_unlimited ? 999999 : (int)$feature->feature_value;
                }
            }
        }

        return [
            // daily_leads: used for the Leads Limit section & stat cards
            'daily_leads' => [
                'used'         => $todayLeadsUsed,
                'limit'        => $dailyLeadsLimit,
                'remaining'    => $dailyLeadsLimit >= 999999 ? 999999 : max(0, $dailyLeadsLimit - $todayLeadsUsed),
                'percentage'   => ($dailyLeadsLimit > 0 && $dailyLeadsLimit < 999999)
                                    ? round(($todayLeadsUsed / $dailyLeadsLimit) * 100) : 0,
                'is_unlimited' => $dailyLeadsLimit >= 999999,
                'has_plan'     => $hasSubscription,
            ],
            // monthly_leads: kept for the "Saved Leads" stat card subtitle
            'monthly_leads' => [
                'used'         => $monthlyLeadsUsed,
                'limit'        => $dailyLeadsLimit,
                'remaining'    => $dailyLeadsLimit >= 999999 ? 999999 : max(0, $dailyLeadsLimit - $todayLeadsUsed),
                'percentage'   => ($dailyLeadsLimit > 0 && $dailyLeadsLimit < 999999)
                                    ? round(($todayLeadsUsed / $dailyLeadsLimit) * 100) : 0,
                'is_unlimited' => $dailyLeadsLimit >= 999999,
                'has_plan'     => $hasSubscription,
            ],
            'search_credits' => [
                'used'         => $monthlySearchesUsed,
                'limit'        => 0,
                'remaining'    => 0,
                'percentage'   => 0,
                'is_unlimited' => $hasSubscription,
                'has_plan'     => $hasSubscription,
            ],
            'api_keys' => [
                'used'         => $apiKeysUsed,
                'limit'        => $apiKeysLimit,
                'percentage'   => $apiKeysLimit > 0 ? round(($apiKeysUsed / $apiKeysLimit) * 100) : 0,
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
            'plain_password' => $user->plain_password,
            'user_type' => $user->user_type,
            'status' => $user->status,
            'login_type' => $user->login_type,
            'email_verified' => $user->email_verified,
            'avatar' => $user->avatar ? asset('public/' . $user->avatar) : null,
            'created_at' => $user->created_at->format('M d, Y h:i A'),
            'last_login' => $user->last_login ? $user->last_login->format('M d, Y h:i A') : 'Never',
            'saved_leads_count' => $user->savedLeads()->count(),
            'search_histories_count' => $user->searchHistories()->count(),
            'monthly_search_credits_used' => \App\Models\ApiUsage::where('user_id', $user->id)
                ->where('date', '>=', now()->startOfMonth()->toDateString())
                ->sum('searches_used'),
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

    /**
     * Toggle company signups enabled/disabled
     */
    public function toggleSignups(\App\Models\User $user)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only toggle for company accounts
        if ($user->user_type !== 'company') {
            return response()->json(['error' => 'This feature is only available for company accounts'], 400);
        }

        $user->update([
            'signups_enabled' => !$user->signups_enabled
        ]);

        return response()->json([
            'success' => true,
            'signups_enabled' => $user->signups_enabled,
            'message' => $user->signups_enabled
                ? 'New signups enabled for this company'
                : 'New signups disabled for this company'
        ]);
    }

}