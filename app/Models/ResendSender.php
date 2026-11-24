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

    /** Most Reply-To addresses one sender may have (e.g. own inbox + Resend receiving address). */
    public const MAX_REPLY_TO = 3;

    /**
     * Parse a comma/semicolon separated Reply-To value into unique lowercase addresses.
     * Returns null when any address is invalid or there are too many; [] when empty.
     *
     * @return list<string>|null
     */
    public static function parseReplyTo(?string $value): ?array
    {
        $list = array_values(array_unique(array_filter(array_map(
            fn ($a) => mb_strtolower(trim($a)),
            preg_split('/[,;\s]+/', (string) $value) ?: []
        ))));
        if (count($list) > self::MAX_REPLY_TO) {
            return null;
        }
        foreach ($list as $address) {
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return null;
            }
        }

        return $list;
    }

    /** @return list<string> Reply-To addresses (empty = replies go to the sender address) */
    public function replyToList(): array
    {
        return self::parseReplyTo($this->reply_to) ?? [];
    }

    /** RFC 5322 "Name <address>" with the name quoted when needed. */
    public function fromHeader(): string
    {
        $name = trim(str_replace(['"', "\r", "\n", '<', '>'], '', $this->from_name));

        return $name === '' ? $this->from_email : '"' . $name . '" <' . $this->from_email . '>';
    }
}
