<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One "send to a lead folder" run: each lead's own AI email, one at a time. */
class LeadFolderSend extends Model
{
    public const RUNNING = 'running';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    public const PROVIDERS = ['gmail' => 'Gmail', 'resend' => 'Resend'];

    protected $guarded = ['id'];

    protected $casts = ['finished_at' => 'datetime'];

    public function items(): HasMany { return $this->hasMany(LeadFolderSendItem::class, 'send_id'); }
    public function folder(): BelongsTo { return $this->belongsTo(LeadFolder::class, 'folder_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function counts(): array
    {
        return $this->items()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')->map(fn ($n) => (int) $n)->all();
    }
}
