<?php

class WelcomeTutorialTracking extends Model
{
    protected $fillable = [
        'user_id',
        'video_started',
        'video_completed',
        'completion_percentage',
        'time_watched',
        'skipped',
        'dont_show_again'
    ];

    protected $casts = [
        'video_started' => 'boolean',
        'video_completed' => 'boolean',
        'completion_percentage' => 'integer',
        'time_watched' => 'integer',
        'skipped' => 'boolean',
        'dont_show_again' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}