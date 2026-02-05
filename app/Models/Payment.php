<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'subscription_id',
        'user_id',
        'payment_method_id',
        'amount',
        'currency',
        'transaction_id',
        'status',
        'notes',
        'metadata',
        'screenshot',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the subscription for this payment.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the user for this payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment method for this payment.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Scope to get only completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get only pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }
}
