<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\Payment;

class SubscriptionController extends Controller
{
    /**
     * Show subscription page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's current subscription logic yahan add karna
        $currentPlan = null; // User ka current plan
        $availablePlans = []; // Available plans
        
        return view('user.subscription', compact('user', 'currentPlan', 'availablePlans'));
    }

    /**
     * Handle subscription upgrade
     */
    public function upgrade(Request $request)
    {
        $user = Auth::user();
        
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
        $request->validate([
            'package_id'        => 'required|exists:packages,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'screenshot'        => 'required|file|mimes:jpg,jpeg,png,gif|max:5120',
        ]);

        $user    = Auth::user();
        $package = Package::find($request->package_id);

        // Screenshot store karo
        $screenshotPath = $request->file('screenshot')->store('payment_screenshots', 'public');

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

        return redirect()->route('home')->with('payment_success', true);
    }
}