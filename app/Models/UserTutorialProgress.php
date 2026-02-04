<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTutorialProgress extends Model
{
    protected $table = 'user_tutorial_progress';

    protected $fillable = [
        'user_id',
        'tutorial_key',
        'completed',
        'completed_at',
        'watch_time',
        'notes'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'watch_time' => 'integer'
    ];

    /**
     * Get the user that owns the tutorial progress.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get completed tutorials
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope to get tutorials for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get completion percentage for a user
     */
    public static function getCompletionPercentage($userId, $totalTutorials = 7)
    {
        $completed = self::forUser($userId)->completed()->count();
        return $totalTutorials > 0 ? round(($completed / $totalTutorials) * 100) : 0;
    }

    /**
     * Mark tutorial as completed
     */
    public static function markCompleted($userId, $tutorialKey)
    {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'tutorial_key' => $tutorialKey
            ],
            [
                'completed' => true,
                'completed_at' => now()
            ]
        );
    }
}