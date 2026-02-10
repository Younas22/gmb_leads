<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Services\CurrencyHelper;

class HomeController extends Controller
{
    /**
     * Display the home page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $allPackages = Package::active()
            ->with('features')
            ->orderBy('price')
            ->get();

        $userPackages    = $allPackages->filter(fn($p) => $p->package_for === 'user');
        $companyPackages = $allPackages->filter(fn($p) => $p->package_for === 'company');

        $paymentMethods = PaymentMethod::active()->get();

        $currency = CurrencyHelper::getVisitorCurrency();

        // Get current plan for authenticated users
        $currentPlan = null;
        if (Auth::check()) {
            $user = Auth::user();
            $currentSubscription = $user->subscriptions()
                ->with(['package.features', 'paymentMethod'])
                ->whereIn('status', ['active', 'pending'])
                ->orderBy('created_at', 'desc')
                ->first();

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
        }

        return view('home', compact('userPackages', 'companyPackages', 'paymentMethods', 'currency', 'currentPlan'));
    }
}