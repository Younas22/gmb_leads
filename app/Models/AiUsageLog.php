<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Metering record for one AI call attempt. Holds no prompt or response text.
 */
class AiUsageLog extends Model
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUSED = 'refused';
    public const STATUS_REJECTED = 'rejected'; // blocked before calling the provider (quota/disabled)

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cache_read_tokens' => 'integer',
        'cache_write_tokens' => 'integer',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
