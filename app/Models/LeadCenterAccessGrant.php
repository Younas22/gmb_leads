<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCenterAccessGrant extends Model
{
    protected $fillable = ['owner_user_id', 'grantee_user_id', 'status'];

    const STATUS_PENDING  = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_REVOKED  = 'revoked';

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function grantee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'grantee_user_id');
    }
}
