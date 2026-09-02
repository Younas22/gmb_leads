<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadCenterMessage extends Model
{
    protected $fillable = ['lead_center_lead_id', 'sender_type', 'message'];

    const SENDER_OUR = 'our';
    const SENDER_CLIENT = 'client';

    public function lead()
    {
        return $this->belongsTo(LeadCenterLead::class, 'lead_center_lead_id');
    }
}
