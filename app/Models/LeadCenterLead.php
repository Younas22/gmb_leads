<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCenterLead extends Model
{
    protected $fillable = [
        'user_id',
        'saved_lead_id',
        'folder_id',
        'company_name',
        'website',
        'country_id',
        'state_id',
        'city_id',
        'status',
        'dedupe_key',
        'contact_links',
    ];

    protected $casts = [
        'contact_links' => 'array',
    ];

    // Channels tracked for outreach — used both for the "contact links" store and the
    // per-message "how was this sent" tag.
    const CONTACT_CHANNELS = ['email', 'facebook', 'whatsapp', 'instagram', 'linkedin', 'contact_form'];

    public static function contactChannelLabels(): array
    {
        return [
            'email' => 'Email',
            'facebook' => 'Facebook',
            'whatsapp' => 'WhatsApp',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
            'contact_form' => 'Contact Form',
        ];
    }

    // Internal status values, in pipeline order
    const STATUS_PENDING    = 'pending';
    const STATUS_CONNECTED  = 'connected';
    const STATUS_RESPONDED  = 'responded';
    const STATUS_FOLLOW_UP  = 'follow_up';
    const STATUS_CLOSED     = 'closed';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING   => 'Pending',
            self::STATUS_CONNECTED => 'Connected',
            self::STATUS_RESPONDED => 'Responded',
            self::STATUS_FOLLOW_UP => 'Follow Up',
            self::STATUS_CLOSED    => 'Closed',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_PENDING   => 'bg-red-100 text-red-700',
            self::STATUS_CONNECTED => 'bg-blue-100 text-blue-700',
            self::STATUS_RESPONDED => 'bg-yellow-100 text-yellow-700',
            self::STATUS_FOLLOW_UP => 'bg-purple-100 text-purple-700',
            self::STATUS_CLOSED    => 'bg-green-100 text-green-700',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savedLead(): BelongsTo
    {
        return $this->belongsTo(SavedLead::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(LeadCenterFolder::class, 'folder_id');
    }

    public function countryRelation(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function stateRelation(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function cityRelation(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    public function messages()
    {
        return $this->hasMany(LeadCenterMessage::class, 'lead_center_lead_id')->orderBy('created_at');
    }

    /**
     * Build the normalized key used for duplicate detection within a user's Lead Center.
     * Prefers the website's host (scheme/www/trailing-slash/case stripped); falls back to
     * the company name when no usable website is present.
     */
    public static function buildDedupeKey(?string $companyName, ?string $website): string
    {
        $website = trim((string) $website);

        if ($website !== '') {
            $host = parse_url((str_contains($website, '://') ? '' : 'https://') . $website, PHP_URL_HOST);
            $host = $host ?: $website;
            $host = strtolower(trim($host));
            $host = preg_replace('/^www\./', '', $host);
            $host = rtrim($host, '/');

            if ($host !== '') {
                return 'site:' . $host;
            }
        }

        $name = strtolower(trim((string) $companyName));

        return 'name:' . $name;
    }
}
