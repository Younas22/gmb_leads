<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateConversion;
use App\Models\AffiliateEarning;
use App\Models\Setting;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\AffiliateService;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function index()
    {
        $stats = AffiliateService::getAdminStats();

        $topAffiliates = AffiliateEarning::with('user:id,name,email,referral_code,affiliate_active')
            ->orderByDesc('total_earned')
            ->take(10)
            ->get();

        $recentConversions = AffiliateConversion::with('user:id,name,email', 'referrer:id,name,email')
            ->latest()
            ->take(10)
            ->get();

        $pendingWithdrawals = WithdrawalRequest::pending()
            ->with('user:id,name,email')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.affiliate.index', compact(
            'stats', 'topAffiliates', 'recentConversions', 'pendingWithdrawals'
        ));
    }

    public function affiliates(Request $request)
    {
        $query = User::whereNotNull('referral_code')
            ->with('affiliateEarning')
            ->withCount(['affiliateClicks', 'referredUsers', 'affiliateConversions'])
            ->orderByDesc('created_at');

        if ($request->search) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('referral_code', 'like', "%{$request->search}%")
            );
        }

        if ($request->status === 'active') {
            $query->where('affiliate_active', true);
        } elseif ($request->status === 'disabled') {
            $query->where('affiliate_active', false);
        }

        $affiliates = $query->paginate(20)->withQueryString();

        return view('admin.affiliate.affiliates', compact('affiliates'));
    }

    public function show(User $user)
    {
        $user->load('affiliateEarning');
        $clicks    = $user->affiliateClicks()->count();
        $signups   = $user->referredUsers()->count();
        $conversions = $user->affiliateConversions()->with('user:id,name,email', 'payment:id,amount')->latest()->get();
        $withdrawals = $user->withdrawalRequests()->latest()->get();

        $fraudFlags = AffiliateService::detectFraud($user->referral_code ?? '', request()->ip());

        return view('admin.affiliate.show', compact(
            'user', 'clicks', 'signups', 'conversions', 'withdrawals', 'fraudFlags'
        ));
    }

    public function toggleStatus(User $user)
    {
        $user->update(['affiliate_active' => !$user->affiliate_active]);

        $status = $user->affiliate_active ? 'enabled' : 'disabled';
        return back()->with('success', "Affiliate account {$status} for {$user->name}.");
    }

    public function updateCommission(Request $request, User $user)
    {
        $request->validate([
            'custom_commission_type'  => 'nullable|in:percent,fixed',
            'custom_commission_value' => 'nullable|numeric|min:0',
        ]);

        $user->update([
            'custom_commission_type'  => $request->custom_commission_type ?: null,
            'custom_commission_value' => $request->custom_commission_value ?: null,
        ]);

        return back()->with('success', 'Custom commission updated.');
    }

    // ── Withdrawals ─────────────────────────────────

    public function withdrawals(Request $request)
    {
        $query = WithdrawalRequest::with('user:id,name,email')->latest();

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->paginate(20)->withQueryString();
        $pendingSum  = WithdrawalRequest::pending()->sum('amount');

        return view('admin.affiliate.withdrawals', compact('withdrawals', 'pendingSum'));
    }

    public function processWithdrawal(Request $request, WithdrawalRequest $withdrawal)
    {
        $request->validate([
            'action' => 'required|in:approve,pay,reject',
            'notes'  => 'nullable|string|max:500',
        ]);

        $ok = AffiliateService::processWithdrawal($withdrawal, $request->action, $request->notes);

        if ($ok) {
            return back()->with('success', 'Withdrawal request updated.');
        }

        return back()->with('error', 'Failed to process withdrawal.');
    }

    // ── Conversions ─────────────────────────────────

    public function conversions(Request $request)
    {
        $query = AffiliateConversion::with('user:id,name,email', 'referrer:id,name,email')->latest();

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $conversions = $query->paginate(20)->withQueryString();

        return view('admin.affiliate.conversions', compact('conversions'));
    }

    public function approveConversion(AffiliateConversion $conversion)
    {
        AffiliateService::approveConversion($conversion);
        return back()->with('success', 'Conversion approved.');
    }

    public function rejectConversion(Request $request, AffiliateConversion $conversion)
    {
        if (!$conversion->isPending()) {
            return back()->with('error', 'Conversion is not pending.');
        }

        // Reverse the pending earning
        $earning = $conversion->referrer->getOrCreateEarning();
        $earning->decrement('pending', $conversion->commission_amount);
        $earning->decrement('total_earned', $conversion->commission_amount);

        $conversion->update(['status' => 'rejected', 'notes' => $request->notes]);

        return back()->with('success', 'Conversion rejected.');
    }

    // ── Settings ────────────────────────────────────

    public function settings()
    {
        $settings = [
            'commission_type'        => Setting::get('affiliate_commission_type', 'percent'),
            'commission_value'       => Setting::get('affiliate_commission_value', 10),
            'cookie_days'            => Setting::get('affiliate_cookie_days', 30),
            'approval_delay_days'    => Setting::get('affiliate_approval_delay_days', 7),
            'min_withdrawal'         => Setting::get('affiliate_min_withdrawal', 20),
        ];

        return view('admin.affiliate.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'commission_type'     => 'required|in:percent,fixed',
            'commission_value'    => 'required|numeric|min:0',
            'cookie_days'         => 'required|integer|min:1|max:365',
            'approval_delay_days' => 'required|integer|min:0|max:90',
            'min_withdrawal'      => 'required|numeric|min:0',
        ]);

        Setting::set('affiliate_commission_type',     $request->commission_type);
        Setting::set('affiliate_commission_value',    $request->commission_value);
        Setting::set('affiliate_cookie_days',         $request->cookie_days);
        Setting::set('affiliate_approval_delay_days', $request->approval_delay_days);
        Setting::set('affiliate_min_withdrawal',      $request->min_withdrawal);

        return back()->with('success', 'Affiliate settings saved.');
    }
}
