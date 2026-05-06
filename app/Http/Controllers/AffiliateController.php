<?php

namespace App\Http\Controllers;

use App\Models\AffiliateConversion;
use App\Models\WithdrawalRequest;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffiliateController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        AffiliateService::ensureReferralCode($user);
        $user->refresh();

        $earning   = $user->getOrCreateEarning();
        $clicks    = $user->affiliateClicks()->count();
        $signups   = $user->referredUsers()->count();
        $converted = $user->affiliateConversions()->where('status', 'approved')->count();

        $conversionRate = $clicks > 0 ? round(($converted / $clicks) * 100, 1) : 0;

        $recentConversions = $user->affiliateConversions()
            ->with('user:id,name,email')
            ->latest()
            ->take(5)
            ->get();

        $clicksChart = $user->affiliateClicks()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $conversionsChart = $user->affiliateConversions()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('user.affiliate.index', compact(
            'user', 'earning', 'clicks', 'signups', 'converted',
            'conversionRate', 'recentConversions', 'clicksChart', 'conversionsChart'
        ));
    }

    public function referrals()
    {
        $user = Auth::user();

        $referredUsers = $user->referredUsers()
            ->select('id', 'name', 'email', 'created_at')
            ->withExists(['subscriptions as has_paid' => fn($q) => $q->where('status', 'active')])
            ->latest()
            ->paginate(20);

        return view('user.affiliate.referrals', compact('user', 'referredUsers'));
    }

    public function earnings()
    {
        $user    = Auth::user();
        $earning = $user->getOrCreateEarning();

        $conversions = $user->affiliateConversions()
            ->with('user:id,name,email', 'payment:id,amount,paid_at')
            ->latest()
            ->paginate(20);

        return view('user.affiliate.earnings', compact('user', 'earning', 'conversions'));
    }

    public function withdrawals()
    {
        $user     = Auth::user();
        $earning  = $user->getOrCreateEarning();
        $requests = $user->withdrawalRequests()->latest()->paginate(15);

        $minWithdrawal = (float) \App\Models\Setting::get('affiliate_min_withdrawal', 20);

        return view('user.affiliate.withdrawals', compact('user', 'earning', 'requests', 'minWithdrawal'));
    }

    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount'  => 'required|numeric|min:0.01',
            'method'  => 'required|in:bank,jazzcash,easypaisa,paypal',
            'account_name'   => 'required|string|max:100',
            'account_number' => 'required|string|max:200',
        ]);

        $result = AffiliateService::requestWithdrawal(
            Auth::user(),
            (float) $request->amount,
            $request->method,
            [
                'account_name'   => $request->account_name,
                'account_number' => $request->account_number,
                'bank_name'      => $request->bank_name,
                'notes'          => $request->notes,
            ]
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message'])->withInput();
    }

    public function generateLink(Request $request)
    {
        $request->validate([
            'utm_source'   => 'nullable|string|max:50',
            'utm_campaign' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();
        AffiliateService::ensureReferralCode($user);
        $user->refresh();

        $url = url('/') . '?ref=' . $user->referral_code;
        if ($request->utm_source) {
            $url .= '&utm=' . urlencode($request->utm_source);
        }
        if ($request->utm_campaign) {
            $url .= '&utm_campaign=' . urlencode($request->utm_campaign);
        }

        return response()->json(['url' => $url]);
    }
}
