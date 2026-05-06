<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\AffiliateService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'subscription.package', 'paymentMethod'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $payments = $query->paginate(20);

        // Stats
        $stats = [
            'total_revenue' => Payment::completed()->sum('amount'),
            'pending_amount' => Payment::pending()->sum('amount'),
            'pending_count' => Payment::pending()->count(),
            'this_month' => Payment::completed()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'today' => Payment::completed()
                ->whereDate('created_at', today())
                ->sum('amount'),
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    /**
     * Update payment status.
     */
    public function updateStatus(Request $request, Payment $payment)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded'
        ]);

        $updateData = ['status' => $request->status];

        if ($request->status === 'completed') {
            $updateData['paid_at'] = now();
        }

        $payment->update($updateData);

        // Process affiliate commission when payment is marked as completed
        if ($request->status === 'completed' && $payment->user) {
            AffiliateService::processConversion($payment);
        }

        // Send invoice email when payment is marked as completed
        if ($request->status === 'completed' && $payment->subscription && $payment->user) {
            try {
                $user = $payment->user;
                $subscription = $payment->subscription;
                $package = $subscription->package;
                $paymentMethod = $payment->paymentMethod;

                $invoiceData = [
                    'invoice_number' => 'INV-' . $subscription->id . '-' . time(),
                    'payment_date' => now()->format('F d, Y'),
                    'amount' => number_format($payment->amount, 2),
                    'payment_method' => $paymentMethod ? $paymentMethod->name : 'Manual Payment',
                    'plan_name' => $package ? $package->name : 'Subscription',
                    'billing_period' => $package ? ucfirst($package->billing_type ?? 'one-time') : 'One-time',
                    'next_billing_date' => $subscription->end_date ? $subscription->end_date->format('F d, Y') : 'N/A',
                ];

                \App\Services\EmailService::sendSubscriptionInvoice($user, $invoiceData);
            } catch (\Exception $e) {
                \Log::error('Invoice email failed for payment ' . $payment->id . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully.'
        ]);
    }
}
