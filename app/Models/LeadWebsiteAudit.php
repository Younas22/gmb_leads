<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evidence-backed result of a bounded website check.
 *
 * verified_findings: list of {id, type, description, evidence, source_url, verification}
 */
class LeadWebsiteAudit extends Model
{
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_UNREACHABLE = 'unreachable';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_INVALID_URL = 'invalid_url';

    protected $guarded = ['id'];

    protected $casts = [
        'extracted_signals' => 'array',
        'verified_findings' => 'array',
        'fetch_log' => 'array',
        'https_available' => 'boolean',
        'audited_at' => 'datetime',
        'http_status' => 'integer',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(SavedLead::class, 'saved_lead_id');
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(AiLeadAnalysis::class, 'analysis_id');
    }

    /** @return array<string, array> findings keyed by id */
    public function findingsById(): array
    {
        $out = [];
        foreach ($this->verified_findings ?? [] as $finding) {
            if (isset($finding['id'])) {
                $out[$finding['id']] = $finding;
            }
        }

        return $out;
    }
}
