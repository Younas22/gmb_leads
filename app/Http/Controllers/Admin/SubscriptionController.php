<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Package;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index()
    {
        $subscriptions = Subscription::with(['user', 'package', 'paymentMethod', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $packages = Package::where('status', 'active')->get();
        $users = User::where('user_type', 'user')->orderBy('name')->get();
        $paymentMethods = PaymentMethod::active()->get();

        // Stats
        $stats = [
            'total' => Subscription::count(),
            'active' => Subscription::where('status', 'active')->count(),
            'expired' => Subscription::where('status', 'expired')->count(),
            'cancelled' => Subscription::where('status', 'cancelled')->count(),
            'pending' => Subscription::where('status', 'pending')->count(),
            'total_revenue' => Payment::completed()->sum('amount'),
        ];

        return view('admin.subscriptions.index', compact('subscriptions', 'packages', 'users', 'paymentMethods', 'stats'));
    }

    /**
     * Store a newly created subscription.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'package_id' => 'required|exists:packages,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'amount_paid' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:active,expired,cancelled',
            'is_trial' => 'boolean',
            'auto_renew' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_trial'] = $request->boolean('is_trial');
        $validated['auto_renew'] = $request->boolean('auto_renew');

        // Check if user already has an active subscription
        $existingActive = Subscription::where('user_id', $validated['user_id'])
            ->where('status', 'active')
            ->first();

        if ($existingActive) {
            // Mark existing as expired
            $existingActive->update(['status' => 'expired']);
        }

        $subscription = Subscription::create($validated);

        // If payment was made, create payment record
        if (!empty($validated['amount_paid']) && $validated['amount_paid'] > 0 && !empty($validated['payment_method_id'])) {
            Payment::create([
                'subscription_id' => $subscription->id,
                'user_id' => $validated['user_id'],
                'payment_method_id' => $validated['payment_method_id'],
                'amount' => $validated['amount_paid'],
                'currency' => 'PKR',
                'status' => 'completed',
                'paid_at' => now(),
            ]);
        }

        return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription created successfully.');
    }

    /**
     * Get subscription data for editing.
     */
    public function edit(Subscription $subscription)
    {
        $subscription->load(['user', 'package', 'paymentMethod', 'payments.paymentMethod']);
        return response()->json($subscription);
    }

    /**
     * Update the specified subscription.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'package_id' => 'required|exists:packages,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'amount_paid' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:active,expired,cancelled',
            'is_trial' => 'boolean',
            'auto_renew' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_trial'] = $request->boolean('is_trial');
        $validated['auto_renew'] = $request->boolean('auto_renew');

        $subscription->update($validated);

        return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription updated successfully.');
    }

    /**
     * Remove the specified subscription.
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->payments()->delete();
        $subscription->delete();

        return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription deleted successfully.');
    }

    /**
     * Toggle subscription status.
     */
    public function toggleStatus(Subscription $subscription)
    {

        $newStatus = $subscription->status === 'active' ? 'cancelled' : 'active';

        $updateData = ['status' => $newStatus];

        // Jab subscription active ho to end_date auto set karo package ke billing_type ke hisab se
        if ($newStatus === 'active') {
            $package = $subscription->package;
            $startDate = $subscription->start_date ?? now();

            if ($package) {
                switch ($package->billing_type) {
                    case 'monthly':
                        $updateData['end_date'] = Carbon::parse($startDate)->addMonth();
                        break;
                    case 'yearly':
                        $updateData['end_date'] = Carbon::parse($startDate)->addYear();
                        break;
                    case 'lifetime':
                        $updateData['end_date'] = null; // Lifetime has no end
                        break;
                    default:
                        // For other billing types, add 30 days by default
                        $updateData['end_date'] = Carbon::parse($startDate)->addDays(30);
                }
            }

            // Set start_date to today if not set
            if (!$subscription->start_date) {
                $updateData['start_date'] = now();
            }

            // Get pending payments before updating
            $pendingPayments = $subscription->payments()->where('status', 'pending')->get();

            // Pending payments ko completed karo
            $subscription->payments()->where('status', 'pending')->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Send invoice email for each approved payment
            if ($pendingPayments->count() > 0) {
                try {
                    $user = $subscription->user;
                    $package = $subscription->package;

                    foreach ($pendingPayments as $payment) {
                        $paymentMethod = $payment->paymentMethod;

                        $invoiceData = [
                            'invoice_number' => 'INV-' . $subscription->id . '-' . time(),
                            'payment_date' => now()->format('F d, Y'),
                            'amount' => number_format($payment->amount, 2),
                            'payment_method' => $paymentMethod ? $paymentMethod->name : 'Manual Payment',
                            'plan_name' => $package->name,
                            'billing_period' => ucfirst($package->billing_type ?? 'one-time'),
                            'next_billing_date' => $updateData['end_date'] ? \Carbon\Carbon::parse($updateData['end_date'])->format('F d, Y') : 'Lifetime',
                        ];

                        $result = \App\Services\EmailService::sendSubscriptionInvoice($user, $invoiceData);
                        if ($result['success']) {
                            \Log::info('Invoice email sent successfully for subscription ' . $subscription->id);
                        } else {
                            \Log::error('Invoice email failed: ' . $result['message']);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Invoice email failed for subscription ' . $subscription->id . ': ' . $e->getMessage());
                }
            }

            // Send subscription start email
            try {
                $user = $subscription->user;
                $package = $subscription->package;

                // Get package features
                $features = $package->features;
                $searchesLimit = 'Standard';
                $exportsLimit = 'Standard';
                $saved_lists = 'Standard';

                foreach ($features as $feature) {
                    if ($feature->feature_key === 'gmb_searches') {
                        $searchesLimit = $feature->is_unlimited ? 'Unlimited' : $feature->feature_value;
                    }
                    if ($feature->feature_key === 'leads_per_month') {
                        $exportsLimit = $feature->is_unlimited ? 'Unlimited' : $feature->feature_value;
                    }
                    if ($feature->feature_key === 'saved_lists') {
                        $saved_lists = $feature->is_unlimited ? 'Unlimited' : $feature->feature_value;
                    }
                }

                $subscriptionData = [
                    'plan_name' => $package->name,
                    'start_date' => $subscription->start_date ? $subscription->start_date->format('F d, Y') : now()->format('F d, Y'),
                    'renewal_date' => $updateData['end_date'] ? \Carbon\Carbon::parse($updateData['end_date'])->format('F d, Y') : 'Lifetime',
                    'searches_limit' => $searchesLimit,
                    'exports_limit' => $exportsLimit,
                    'saved_lists' => $saved_lists,
                ];

                $result = \App\Services\EmailService::sendSubscriptionStart($user, $subscriptionData);
                if ($result['success']) {
                    \Log::info('Subscription start email sent successfully for subscription ' . $subscription->id);
                } else {
                    \Log::error('Subscription start email failed: ' . $result['message']);
                }
            } catch (\Exception $e) {
                \Log::error('Subscription start email failed for subscription ' . $subscription->id . ': ' . $e->getMessage());
            }
        }

        $subscription->update($updateData);

        return response()->json([
            'success' => true,
            'status' => $subscription->status,
            'end_date' => $subscription->end_date ? $subscription->end_date->format('M d, Y') : 'Lifetime',
            'message' => 'Subscription status updated successfully.'
        ]);
    }

    /**
     * Add payment to subscription.
     */
    public function addPayment(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_id' => 'nullable|string|max:100',
            'status' => 'required|in:pending,completed,failed,refunded',
            'notes' => 'nullable|string',
        ]);

        $validated['subscription_id'] = $subscription->id;
        $validated['user_id'] = $subscription->user_id;
        $validated['currency'] = 'PKR';

        if ($validated['status'] === 'completed') {
            $validated['paid_at'] = now();
        }

        Payment::create($validated);

        return redirect()->route('admin.subscriptions.index')->with('success', 'Payment added successfully.');
    }
}
