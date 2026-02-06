<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
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

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully.'
        ]);
    }
}
