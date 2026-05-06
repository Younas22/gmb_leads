<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'method',
        'payment_details',
        'status',
        'admin_notes',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($q)  { return $q->where('status', 'pending'); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
    public function scopePaid($q)     { return $q->where('status', 'paid'); }

    public function isPending()  { return $this->status === 'pending'; }
    public function isApproved() { return $this->status === 'approved'; }
    public function isPaid()     { return $this->status === 'paid'; }

    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'bank'      => 'Bank Transfer',
            'jazzcash'  => 'JazzCash',
            'easypaisa' => 'EasyPaisa',
            'paypal'    => 'PayPal',
            default     => ucfirst($this->method),
        };
    }
}
