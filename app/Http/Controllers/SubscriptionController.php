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

        // Daily leads: how many leads saved today vs daily_leads_limit
        $todayLeadsUsed = \App\Models\SavedLead::whereIn('user_id', $userIds)
            ->whereDate('created_at', today())
            ->count();

        $dailyLeadsLimit = 0;
        $exportLeadsUnlimited = false;
        $maxDevices = 1;

        if ($currentPlan && $currentPlan['package']) {
            foreach ($currentPlan['package']->features as $feature) {
                if ($feature->feature_key === 'daily_leads_limit') {
                    $dailyLeadsLimit = ($feature->is_unlimited || $feature->feature_value === 'unlimited') ? 999999 : (int)$feature->feature_value;
                }
                if ($feature->feature_key === 'export_leads') {
                    $exportLeadsUnlimited = ($feature->is_unlimited || $feature->feature_value === 'unlimited');
                }
                if ($feature->feature_key === 'max_devices') {
                    $maxDevices = (int)$feature->feature_value;
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

        // Usage data (daily leads vs limit)
        $usageData = [
            'daily_leads' => [
                'used'       => $todayLeadsUsed,
                'limit'      => $dailyLeadsLimit,
                'unlimited'  => $dailyLeadsLimit >= 999999,
                'remaining'  => $dailyLeadsLimit >= 999999 ? 999999 : max(0, $dailyLeadsLimit - $todayLeadsUsed),
                'percentage' => ($dailyLeadsLimit > 0 && $dailyLeadsLimit < 999999)
                                    ? round(($todayLeadsUsed / $dailyLeadsLimit) * 100) : 0,
            ],
            'export_leads' => [
                'unlimited' => $exportLeadsUnlimited,
            ],
            'max_devices' => $maxDevices,
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
     * Cancel active subscription
     */
    public function cancelPlan(Request $request)
    {
        $user = Auth::user();

        if ($user->isTeamMember()) {
            return redirect()->route('user.dashboard')->with('error', 'Team members cannot manage subscriptions.');
        }

        $subscription = $user->subscriptions()
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$subscription) {
            return redirect()->route('user.subscription')->with('error', 'No active subscription found.');
        }

        $subscription->update(['status' => 'cancelled']);

        return redirect()->route('user.subscription')->with('success', 'Your plan has been cancelled successfully.');
    }

    /**
     * Free plan auto-approve — no payment required
     */
    public function applyFreePlan(Request $request)
    {
        $user = Auth::user();

        if ($user->isTeamMember()) {
            return redirect()->route('user.dashboard')->with('error', 'Team members cannot manage subscriptions. Please contact your company administrator.');
        }

        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $package = Package::find($request->package_id);

        if ((float)$package->price !== 0.0) {
            return redirect()->route('user.subscription')->with('error', 'This plan requires payment.');
        }

        // Cancel any existing active/pending subscription
        $user->subscriptions()
            ->whereIn('status', ['active', 'pending'])
            ->update(['status' => 'cancelled']);

        // Create active subscription immediately
        $endDate = $package->billing_type === 'yearly'
            ? now()->addYear()
            : now()->addMonth();

        Subscription::create([
            'package_id'  => $package->id,
            'user_id'     => $user->id,
            'amount_paid' => 0,
            'start_date'  => now(),
            'end_date'    => $endDate,
            'status'      => 'active',
        ]);

        return redirect()->route('user.subscription')->with('success', 'Free plan activated successfully! Enjoy your access.');
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