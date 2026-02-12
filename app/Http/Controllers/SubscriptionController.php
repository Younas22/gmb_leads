<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\CurrencyHelper;

class SubscriptionController extends Controller
{
    /**
     * Show subscription page
     */
    public function index()
    {
        $user = Auth::user();

        // Team members cannot access subscription page
        // They inherit subscription from their company owner
        if ($user->isTeamMember()) {
            return redirect()->route('user.dashboard')->with('error', 'Team members cannot manage subscriptions. Please contact your company administrator.');
        }

        // Get user's current active or pending subscription
        $currentSubscription = $user->subscriptions()
            ->with(['package.features', 'paymentMethod'])
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('created_at', 'desc')
            ->first();

        // Get current plan details
        $currentPlan = $currentSubscription ? [
            'subscription' => $currentSubscription,
            'package' => $currentSubscription->package,
            'status' => $currentSubscription->status,
            'is_pending' => $currentSubscription->status === 'pending',
            'is_active' => $currentSubscription->status === 'active',
            'start_date' => $currentSubscription->start_date,
            'end_date' => $currentSubscription->end_date,
            'amount_paid' => $currentSubscription->amount_paid,
        ] : null;

        // Get available packages based on user type
        // If user is company, show company packages, otherwise show user packages
        if ($user->isCompany()) {
            $availablePlans = Package::active()
                ->forCompany()
                ->with('features')
                ->orderBy('price', 'asc')
                ->get();
        } else {
            $availablePlans = Package::active()
                ->forUser()
                ->with('features')
                ->orderBy('price', 'asc')
                ->get();
        }

        // Get all active payment methods
        $paymentMethods = PaymentMethod::where('is_active', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Calculate usage statistics
        $currentMonth = now()->startOfMonth();

        // Get all user IDs that should be counted for this quota (company + all team members)
        // Since team members can't access this page, $user is always the company owner
        $userIds = [$user->id];
        if ($user->isCompany()) {
            $teamMemberIds = $user->teamMembers()->pluck('id')->toArray();
            $userIds = array_merge($userIds, $teamMemberIds);
        }

        // Monthly leads - count saved leads this month for company + all team members
        $monthlyLeadsUsed = \App\Models\SavedLead::whereIn('user_id', $userIds)
            ->where('created_at', '>=', $currentMonth)
            ->count();

        // Get limits from current package or default to 0 (no access without subscription)
        $monthlyLeadsLimit = 0; // Default: no subscription
        $monthlySearchesLimit = 0; // Default: no subscription (gmb_searches is monthly limit)
        $savedListsLimit = 0; // Default: no subscription
        $exportLeadsLimit = 0; // Default: no subscription

        // Monthly searches count
        $monthlySearchesUsed = \App\Models\SearchHistory::whereIn('user_id', $userIds)
            ->where('created_at', '>=', $currentMonth)
            ->count();

        // Saved leads count (total)
        $savedListsUsed = \App\Models\SavedLead::whereIn('user_id', $userIds)->count();

        // Monthly exports count
        $monthlyExportsUsed = \App\Models\ExportHistory::whereIn('user_id', $userIds)
            ->where('created_at', '>=', $currentMonth)
            ->count();

        if ($currentPlan && $currentPlan['package']) {
            $features = $currentPlan['package']->features;

            foreach ($features as $feature) {
                if ($feature->feature_key === 'leads_per_month') {
                    $monthlyLeadsLimit = $feature->is_unlimited ? 999999 : (int)$feature->feature_value;
                }
                if ($feature->feature_key === 'gmb_searches') {
                    $monthlySearchesLimit = $feature->is_unlimited ? 999999 : (int)$feature->feature_value;
                }
                if ($feature->feature_key === 'saved_lists') {
                    $savedListsLimit = $feature->is_unlimited ? 999999 : (int)$feature->feature_value;
                }
                if ($feature->feature_key === 'export_leads') {
                    $exportLeadsLimit = $feature->is_unlimited ? 999999 : (int)$feature->feature_value;
                }
            }
        }

        // Calculate total searches this month (for analytics) - company + all team members
        $monthlySearches = \App\Models\SearchHistory::whereIn('user_id', $userIds)
            ->where('created_at', '>=', $currentMonth)
            ->count();

        // Calculate last month searches for comparison
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $lastMonthSearches = \App\Models\SearchHistory::whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->count();

        // Calculate last month leads for comparison
        $lastMonthLeads = \App\Models\SavedLead::whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->count();

        // Calculate percentage changes
        $searchesChange = $lastMonthSearches > 0
            ? round((($monthlySearches - $lastMonthSearches) / $lastMonthSearches) * 100)
            : ($monthlySearches > 0 ? 100 : 0);

        $leadsChange = $lastMonthLeads > 0
            ? round((($monthlyLeadsUsed - $lastMonthLeads) / $lastMonthLeads) * 100)
            : ($monthlyLeadsUsed > 0 ? 100 : 0);

        // Get billing history - last 5 completed payments
        $billingHistory = Payment::where('user_id', $user->id)
            ->with(['subscription.package', 'paymentMethod'])
            ->completed()
            ->orderBy('paid_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Get user's payment method (if any)
        $userPaymentMethod = null;
        if ($currentPlan && $currentPlan['subscription']->paymentMethod) {
            $userPaymentMethod = $currentPlan['subscription']->paymentMethod;
        }

        // Usage data
        $usageData = [
            'monthly_searches' => [
                'used' => $monthlySearchesUsed,
                'limit' => $monthlySearchesLimit,
                'remaining' => max(0, $monthlySearchesLimit - $monthlySearchesUsed),
                'percentage' => $monthlySearchesLimit > 0 ? round(($monthlySearchesUsed / $monthlySearchesLimit) * 100) : 0,
            ],
            'monthly_leads' => [
                'used' => $monthlyLeadsUsed,
                'limit' => $monthlyLeadsLimit,
                'remaining' => max(0, $monthlyLeadsLimit - $monthlyLeadsUsed),
                'percentage' => $monthlyLeadsLimit > 0 ? round(($monthlyLeadsUsed / $monthlyLeadsLimit) * 100) : 0,
            ],
            'saved_lists' => [
                'used' => $savedListsUsed,
                'limit' => $savedListsLimit,
                'remaining' => max(0, $savedListsLimit - $savedListsUsed),
                'percentage' => $savedListsLimit > 0 ? round(($savedListsUsed / $savedListsLimit) * 100) : 0,
            ],
            'monthly_exports' => [
                'used' => $monthlyExportsUsed,
                'limit' => $exportLeadsLimit,
                'remaining' => max(0, $exportLeadsLimit - $monthlyExportsUsed),
                'percentage' => $exportLeadsLimit > 0 ? round(($monthlyExportsUsed / $exportLeadsLimit) * 100) : 0,
            ],
        ];

        // Analytics data
        $analyticsData = [
            'monthly_searches' => $monthlySearches,
            'searches_change' => $searchesChange,
            'monthly_leads' => $monthlyLeadsUsed,
            'leads_change' => $leadsChange,
        ];

        $currency = CurrencyHelper::getVisitorCurrency();

        return view('user.subscription', compact(
            'user',
            'currentPlan',
            'availablePlans',
            'paymentMethods',
            'usageData',
            'analyticsData',
            'billingHistory',
            'userPaymentMethod',
            'currency'
        ));
    }

    /**
     * Handle subscription upgrade
     */
    public function upgrade(Request $request)
    {
        $user = Auth::user();

        // Team members cannot upgrade subscription
        if ($user->isTeamMember()) {
            return redirect()->route('user.dashboard')->with('error', 'Team members cannot manage subscriptions. Please contact your company administrator.');
        }

        $request->validate([
            'plan_id' => 'required|string',
        ]);
        
        // Subscription upgrade logic yahan add karna
        
        return redirect()->route('user.subscription')->with('success', 'Subscription upgraded successfully');
    }

    /**
     * User payment screenshot submit — pending subscription banao
     */
    public function submitPayment(Request $request)
    {
        $user = Auth::user();

        // Team members cannot submit payment
        if ($user->isTeamMember()) {
            return redirect()->route('user.dashboard')->with('error', 'Team members cannot manage subscriptions. Please contact your company administrator.');
        }

        $request->validate([
            'package_id'        => 'required|exists:packages,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'screenshot'        => 'required|file|mimes:jpg,jpeg,png,gif|max:5120',
        ]);
        $package = Package::find($request->package_id);

        // Screenshot store karo - public/images/payments folder mein
        $file = $request->file('screenshot');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images/payments'), $filename);
        $screenshotPath = 'images/payments/' . $filename;

        // Pending subscription banao
        $subscription = Subscription::create([
            'package_id'        => $package->id,
            'user_id'           => $user->id,
            'payment_method_id' => $request->payment_method_id,
            'amount_paid'       => $package->price,
            'start_date'        => now(),
            'status'            => 'pending',
        ]);

        // Pending payment record banao
        Payment::create([
            'subscription_id'   => $subscription->id,
            'user_id'           => $user->id,
            'payment_method_id' => $request->payment_method_id,
            'amount'            => $package->price,
            'currency'          => $package->currency ?? 'PKR',
            'status'            => 'pending',
            'screenshot'        => $screenshotPath,
        ]);

        // NOTE: Invoice email will be sent when admin approves the payment
        // Not sending invoice email here because payment is still pending

        return redirect()->back()->with('payment_success', true);
    }
}