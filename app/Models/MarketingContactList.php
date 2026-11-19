<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MarketingContactList extends Model
{
    protected $guarded = ['id'];

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(MarketingContact::class, 'marketing_contact_list_members', 'list_id', 'contact_id');
    }
}
