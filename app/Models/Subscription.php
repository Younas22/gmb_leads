<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'package_id',
        'user_id',
        'company_id',
        'payment_method_id',
        'amount_paid',
        'start_date',
        'end_date',
        'status',
        'is_trial',
        'auto_renew',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_trial' => 'boolean',
        'auto_renew' => 'boolean',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Get the package for this subscription.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the user for this subscription.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment method for this subscription.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get payments for this subscription.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope to get only active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if subscription is active.
     */
    public function isActive()
    {
        return $this->status === 'active' && ($this->end_date === null || $this->end_date->isFuture());
    }

    /**
     * Get the total amount paid for this subscription.
     */
    public function getTotalPaidAttribute()
    {
        return $this->payments()->completed()->sum('amount');
    }
}
