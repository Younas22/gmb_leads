<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A From identity on one of the customer's Resend domains. */
class ResendSender extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_default' => 'boolean'];

    public function domain(): BelongsTo { return $this->belongsTo(ResendDomain::class, 'domain_id'); }

    public function canSend(): bool
    {
        return $this->domain && $this->domain->canSend()
            && str_ends_with(mb_strtolower($this->from_email), '@' . mb_strtolower($this->domain->name));
    }

    /** RFC 5322 "Name <address>" with the name quoted when needed. */
    public function fromHeader(): string
    {
        $name = trim(str_replace(['"', "\r", "\n", '<', '>'], '', $this->from_name));

        return $name === '' ? $this->from_email : '"' . $name . '" <' . $this->from_email . '>';
    }
}
