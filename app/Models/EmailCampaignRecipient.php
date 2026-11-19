<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaignRecipient extends Model
{
    protected $guarded = ['id'];

    public function campaign(): BelongsTo { return $this->belongsTo(EmailCampaign::class, 'campaign_id'); }
    public function contact(): BelongsTo { return $this->belongsTo(MarketingContact::class, 'contact_id'); }
    public function email(): BelongsTo { return $this->belongsTo(OutboundEmail::class, 'outbound_email_id'); }
}
