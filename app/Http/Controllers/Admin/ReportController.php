<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\SavedLead;
use App\Models\SearchHistory;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\ExportHistory;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RevenueReportExport;
use App\Exports\UserGrowthReportExport;
use App\Exports\LeadsReportExport;
use App\Exports\SearchReportExport;
use App\Exports\PackagePerformanceReportExport;

class ReportController extends Controller
{
    /**
     * Revenue Report
     */
    public function revenue(Request $request)
    {
        // Get filter parameters
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Calculate stats
        $totalRevenue = Payment::completed()->sum('amount');
        $thisMonthRevenue = Payment::completed()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        $pendingPayments = Payment::pending()->sum('amount');
        $completedPaymentsCount = Payment::completed()->count();

        // Last 6 months revenue data
        $last6MonthsData = [];
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $revenue = Payment::completed()
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');

            $last6MonthsData[] = $revenue;
            $labels[] = $date->format('M Y');
        }

        // Monthly breakdown for table
        $monthlyData = Payment::completed()
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as payments_count')
            )
            ->when($month && $year, function($query) use ($month, $year) {
                return $query->whereMonth('created_at', $month)
                            ->whereYear('created_at', $year);
            })
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        return view('admin.reports.revenue', compact(
            'totalRevenue',
            'thisMonthRevenue',
            'pendingPayments',
            'completedPaymentsCount',
            'last6MonthsData',
            'labels',
            'monthlyData',
            'month',
            'year'
        ));
    }

    /**
     * User Growth Report
     */
    public function userGrowth(Request $request)
    {
        $period = $request->input('period', '6months');

        // Calculate stats
        $totalUsers = User::where('user_type', 'user')->count();
        $thisMonthUsers = User::where('user_type', 'user')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $activeUsers = User::where('user_type', 'user')
            ->where('status', 'active')
            ->count();
        $inactiveUsers = User::where('user_type', 'user')
            ->where('status', 'inactive')
            ->count();

        // User growth over time
        $months = $period === '12months' ? 12 : 6;
        $growthData = [];
        $labels = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = User::where('user_type', 'user')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $growthData[] = $count;
            $labels[] = $date->format('M Y');
        }

        // Monthly breakdown table
        $monthlyUsers = collect();
        $runningTotal = 0;

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $newUsersCount = User::where('user_type', 'user')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $runningTotal += $newUsersCount;

            $monthlyUsers->push((object)[
                'year' => $date->year,
                'month' => $date->month,
                'users_count' => $newUsersCount,
                'total_users' => $runningTotal
            ]);
        }

        $monthlyUsers = $monthlyUsers->reverse();

        return view('admin.reports.user-growth', compact(
            'totalUsers',
            'thisMonthUsers',
            'activeUsers',
            'inactiveUsers',
            'growthData',
            'labels',
            'monthlyUsers',
            'period'
        ));
    }

    /**
     * Leads Report
     */
    public function leads(Request $request)
    {
        $dateRange = $request->input('range', '30');

        // Calculate stats
        $totalLeads = SavedLead::count();
        $thisMonthLeads = SavedLead::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $averageLeadsPerUser = User::where('user_type', 'user')
            ->withCount('savedLeads')
            ->get()
            ->avg('saved_leads_count');
        $uniqueUsers = SavedLead::distinct('user_id')->count('user_id');

        // Leads over time
        $days = $dateRange === 'all' ? 30 : (int)$dateRange;
        $leadsData = [];
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = SavedLead::whereDate('created_at', $date->toDateString())->count();
            $leadsData[] = $count;
            $labels[] = $date->format('M d');
        }

        // Top users by leads
        $topUsers = User::where('user_type', 'user')
            ->withCount('savedLeads')
            ->orderByDesc('saved_leads_count')
            ->limit(10)
            ->get();

        return view('admin.reports.leads', compact(
            'totalLeads',
            'thisMonthLeads',
            'averageLeadsPerUser',
            'uniqueUsers',
            'leadsData',
            'labels',
            'topUsers',
            'dateRange'
        ));
    }

    /**
     * Search Report
     */
    public function search(Request $request)
    {
        $dateRange = $request->input('range', '30');

        // Calculate stats
        $totalSearches = SearchHistory::count();
        $successfulSearches = SearchHistory::where('status', 'success')->count();
        $averageResultsPerSearch = SearchHistory::where('status', 'success')->avg('results_count') ?? 0;
        $uniqueSearchers = SearchHistory::distinct('user_id')->count('user_id');

        // Searches over time
        $days = $dateRange === 'all' ? 30 : (int)$dateRange;
        $searchData = [];
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = SearchHistory::whereDate('created_at', $date->toDateString())->count();
            $searchData[] = $count;
            $labels[] = $date->format('M d');
        }

        // Top search queries
        $topQueries = SearchHistory::select('query', DB::raw('COUNT(*) as count'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return view('admin.reports.search', compact(
            'totalSearches',
            'successfulSearches',
            'averageResultsPerSearch',
            'uniqueSearchers',
            'searchData',
            'labels',
            'topQueries',
            'dateRange'
        ));
    }

    /**
     * Package Performance Report
     */
    public function packagePerformance(Request $request)
    {
        // Calculate stats
        $totalPackages = Package::count();
        $activePackages = Package::where('status', 'active')->count();
        $totalSubscriptions = Subscription::count();
        $mostPopularPackage = Package::withCount('subscriptions')
            ->orderByDesc('subscriptions_count')
            ->first();

        // Package distribution
        $packageData = Package::withCount(['subscriptions' => function($query) {
            $query->where('status', 'active');
        }])
        ->get()
        ->map(function($package) use ($totalSubscriptions) {
            return [
                'name' => $package->name,
                'billing_type' => $package->billing_type,
                'package_for' => $package->package_for,
                'count' => $package->subscriptions_count,
                'percentage' => $totalSubscriptions > 0 ? round(($package->subscriptions_count / $totalSubscriptions) * 100, 1) : 0,
                'revenue' => Payment::completed()
                    ->whereHas('subscription', function($query) use ($package) {
                        $query->where('package_id', $package->id);
                    })
                    ->sum('amount')
            ];
        });

        $labels = $packageData->map(function($package) {
            return ucfirst($package['billing_type']).' '.$package['name'].' ('.ucfirst($package['package_for']).')';
        })->toArray();
        $counts = $packageData->pluck('count')->toArray();

        return view('admin.reports.package-performance', compact(
            'totalPackages',
            'activePackages',
            'totalSubscriptions',
            'mostPopularPackage',
            'packageData',
            'labels',
            'counts'
        ));
    }

    /**
     * Export Activity Report
     */
    public function exportActivity(Request $request)
    {
        $dateRange = $request->input('range', '30');

        // Calculate stats (using SavedLead exports as proxy for now)
        $totalExports = SavedLead::whereNotNull('created_at')->distinct('user_id')->count();
        $thisMonthExports = SavedLead::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->distinct('user_id')
            ->count();
        $averageExportsPerUser = $totalExports > 0 ? round(SavedLead::count() / $totalExports, 1) : 0;
        $mostActiveExporter = User::withCount('savedLeads')
            ->orderByDesc('saved_leads_count')
            ->first();

        // Export activity over time
        $days = $dateRange === 'all' ? 30 : (int)$dateRange;
        $exportData = [];
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = SavedLead::whereDate('created_at', $date->toDateString())->count();
            $exportData[] = $count;
            $labels[] = $date->format('M d');
        }

        return view('admin.reports.export-activity', compact(
            'totalExports',
            'thisMonthExports',
            'averageExportsPerUser',
            'mostActiveExporter',
            'exportData',
            'labels',
            'dateRange'
        ));
    }

    /**
     * Top Performers Report
     */
    public function topPerformers()
    {
        // Top users by revenue
        $topByRevenue = User::where('user_type', 'user')
            ->withSum(['subscriptions as total_paid' => function($query) {
                $query->join('payments', 'subscriptions.id', '=', 'payments.subscription_id')
                      ->where('payments.status', 'completed');
            }], 'payments.amount')
            ->orderByDesc('total_paid')
            ->limit(10)
            ->get();

        // Top users by leads
        $topByLeads = User::where('user_type', 'user')
            ->withCount('savedLeads')
            ->orderByDesc('saved_leads_count')
            ->limit(10)
            ->get();

        // Top users by searches
        $topBySearches = User::where('user_type', 'user')
            ->withCount('searchHistories')
            ->orderByDesc('search_histories_count')
            ->limit(10)
            ->get();

        // Most active users this month
        $mostActiveThisMonth = User::where('user_type', 'user')
            ->withCount(['searchHistories' => function($query) {
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
            }])
            ->orderByDesc('search_histories_count')
            ->limit(10)
            ->get();

        return view('admin.reports.top-performers', compact(
            'topByRevenue',
            'topByLeads',
            'topBySearches',
            'mostActiveThisMonth'
        ));
    }

    /**
     * User Activity Report
     */
    public function userActivity(Request $request)
    {
        $dateRange = $request->input('range', '30');

        // Calculate stats
        $days = $dateRange === 'all' ? 30 : (int)$dateRange;
        $startDate = now()->subDays($days);

        $activeUsers = User::where('user_type', 'user')
            ->where('last_login', '>=', $startDate)
            ->count();
        $totalLogins = User::where('user_type', 'user')
            ->where('last_login', '>=', $startDate)
            ->count();
        $averageSessionsPerUser = $activeUsers > 0 ? round($totalLogins / $activeUsers, 1) : 0;
        $newUsersInPeriod = User::where('user_type', 'user')
            ->where('created_at', '>=', $startDate)
            ->count();

        // Activity over time
        $activityData = [];
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = User::where('user_type', 'user')
                ->whereDate('last_login', $date->toDateString())
                ->count();
            $activityData[] = $count;
            $labels[] = $date->format('M d');
        }

        // Recent active users
        $recentActiveUsers = User::where('user_type', 'user')
            ->whereNotNull('last_login')
            ->orderByDesc('last_login')
            ->limit(20)
            ->get();

        return view('admin.reports.user-activity', compact(
            'activeUsers',
            'totalLogins',
            'averageSessionsPerUser',
            'newUsersInPeriod',
            'activityData',
            'labels',
            'recentActiveUsers',
            'dateRange'
        ));
    }

    /**
     * System Overview Report
     */
    public function systemOverview()
    {
        // Overall system stats
        $stats = [
            'total_users' => User::where('user_type', 'user')->count(),
            'total_revenue' => Payment::completed()->sum('amount'),
            'total_searches' => SearchHistory::count(),
            'total_leads' => SavedLead::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'pending_payments' => Payment::pending()->count(),
            'total_packages' => Package::count(),
            'active_packages' => Package::where('status', 'active')->count(),
        ];

        // Monthly trends (last 12 months)
        $monthlyTrends = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyTrends[] = [
                'month' => $date->format('M Y'),
                'users' => User::where('user_type', 'user')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
                'revenue' => Payment::completed()
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('amount'),
                'searches' => SearchHistory::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }

        // Growth rates
        $growthRates = [
            'users' => $this->calculateGrowthRate(User::where('user_type', 'user')),
            'revenue' => $this->calculateGrowthRate(Payment::completed(), 'amount'),
            'searches' => $this->calculateGrowthRate(SearchHistory::query()),
        ];

        return view('admin.reports.system-overview', compact('stats', 'monthlyTrends', 'growthRates'));
    }

    /**
     * Calculate growth rate (month over month)
     */
    private function calculateGrowthRate($query, $sumColumn = null)
    {
        $thisMonth = $sumColumn
            ? $query->clone()->whereMonth('created_at', now()->month)->sum($sumColumn)
            : $query->clone()->whereMonth('created_at', now()->month)->count();

        $lastMonth = $sumColumn
            ? $query->clone()->whereMonth('created_at', now()->subMonth()->month)->sum($sumColumn)
            : $query->clone()->whereMonth('created_at', now()->subMonth()->month)->count();

        if ($lastMonth == 0) {
            return $thisMonth > 0 ? 100 : 0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    /**
     * Export Revenue Report to Excel
     */
    public function exportRevenue()
    {
        return Excel::download(new RevenueReportExport, 'revenue-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export User Growth Report to Excel
     */
    public function exportUserGrowth()
    {
        return Excel::download(new UserGrowthReportExport, 'user-growth-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Leads Report to Excel
     */
    public function exportLeads()
    {
        return Excel::download(new LeadsReportExport, 'leads-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Search Report to Excel
     */
    public function exportSearch()
    {
        return Excel::download(new SearchReportExport, 'search-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Package Performance Report to Excel
     */
    public function exportPackagePerformance()
    {
        return Excel::download(new PackagePerformanceReportExport, 'package-performance-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * All Users Leads Report
     */
    public function allLeads(Request $request)
    {
        // Build query with relationships
        $query = SavedLead::with(['user', 'cityRelation', 'stateRelation', 'countryRelation']);

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('month')) {
            $date = \Carbon\Carbon::parse($request->month);
            $query->whereMonth('created_at', $date->month)
                  ->whereYear('created_at', $date->year);
        }

        if ($request->filled('category')) {
            $query->where('category', 'like', '%' . $request->category . '%');
        }

        if ($request->filled('country_id')) {
            $query->where('country', $request->country_id);
        }

        if ($request->filled('state_id')) {
            $query->where('state', $request->state_id);
        }

        if ($request->filled('city_id')) {
            $query->where('city', $request->city_id);
        }

        if ($request->filled('contact_status')) {
            $query->where('contact_status', $request->contact_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        // Get summary stats
        $totalLeads = SavedLead::count();
        $leadsThisMonth = SavedLead::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $totalUsersWithLeads = SavedLead::distinct('user_id')->count('user_id');
        $mostActiveUser = User::withCount('savedLeads')
            ->orderByDesc('saved_leads_count')
            ->first();

        // Paginate results
        $leads = $query->orderByDesc('created_at')->paginate(50);

        // Get all users for filter dropdown
        $users = User::where('user_type', 'user')
            ->orderBy('first_name')
            ->orderBy('name')
            ->get();

        // Get countries, states, cities for filters
        $countries = \App\Models\Country::orderBy('name')->get();

        return view('admin.reports.all-leads', compact(
            'leads',
            'users',
            'countries',
            'totalLeads',
            'leadsThisMonth',
            'totalUsersWithLeads',
            'mostActiveUser'
        ));
    }

    /**
     * Export All Leads Report to Excel
     */
    public function exportAllLeads(Request $request)
    {
        return Excel::download(
            new \App\Exports\AllLeadsReportExport($request->all()),
            'all-leads-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
