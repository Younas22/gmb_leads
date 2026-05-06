<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateConversion extends Model
{
    protected $fillable = [
        'user_id',
        'referrer_id',
        'payment_id',
        'referral_code',
        'sale_amount',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'status',
        'approved_at',
        'available_at',
        'notes',
    ];

    protected $casts = [
        'sale_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'available_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopePending($q)  { return $q->where('status', 'pending'); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
    public function scopeRejected($q) { return $q->where('status', 'rejected'); }

    public function isPending()  { return $this->status === 'pending'; }
    public function isApproved() { return $this->status === 'approved'; }
}
