<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SearchHistory extends Model
{
    protected $table = 'search_history';
    
    // Remove this line if you want to use timestamps
    // public $timestamps = false;
    
    // Or if you want custom timestamp columns, use these:
    // const CREATED_AT = 'created_at';
    // const UPDATED_AT = null; // if you only want created_at
    
    protected $fillable = [
        'user_id',
        'query',
        'location',
        'latitude',
        'longitude',
        'radius',
        'results_count',
        'api_used',
        'execution_time',
        'status',           // Add this for success/failed status
        'error_message',    // Add this for error messages
        'response_time',    // Add this for API response time
        'results_data',     // Add this to store JSON results
        'created_at'        // Add this if timestamps = false
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'execution_time' => 'float',
        'response_time' => 'float',
        'results_data' => 'array',      // Cast JSON to array
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Add default values
    protected $attributes = [
        'status' => 'pending',
        'api_used' => 'Google Places'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Get recent searches for a user
    public static function getRecentForUser($userId, $limit = 10)
    {
        return static::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    // Scope for successful searches
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    // Scope for failed searches
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Scope for date range filtering
    public function scopeDateRange($query, $days)
    {
        if ($days && $days !== 'all') {
            return $query->where('created_at', '>=', Carbon::now()->subDays($days));
        }
        return $query;
    }

    // Get formatted location string
    public function getFormattedLocationAttribute()
    {
        if ($this->location) {
            return $this->location;
        }
        
        if ($this->latitude && $this->longitude) {
            return $this->latitude . ', ' . $this->longitude;
        }
        
        return 'Not specified';
    }

    // Check if search was successful
    public function isSuccessful()
    {
        return $this->status === 'success';
    }

    // Check if search failed
    public function hasFailed()
    {
        return $this->status === 'failed';
    }

    // Get human readable response time
    public function getFormattedResponseTimeAttribute()
    {
        if ($this->response_time) {
            return number_format($this->response_time, 2) . 's';
        }
        return 'N/A';
    }

    // Get stats for user
    public static function getUserStats($userId)
    {
        $totalSearches = static::where('user_id', $userId)->count();
        
        $successfulSearches = static::where('user_id', $userId)
            ->where('status', 'success')
            ->count();
        
        $avgResults = static::where('user_id', $userId)
            ->where('status', 'success')
            ->avg('results_count') ?? 0;
        
        $topQuery = static::where('user_id', $userId)
            ->select('query')
            ->groupBy('query')
            ->orderByRaw('COUNT(*) DESC')
            ->first();
        
        return [
            'total_searches' => $totalSearches,
            'successful_searches' => $successfulSearches,
            'failed_searches' => $totalSearches - $successfulSearches,
            'success_rate' => $totalSearches > 0 ? round(($successfulSearches / $totalSearches) * 100, 1) : 0,
            'avg_results' => $avgResults,
            'top_query' => $topQuery ? $topQuery->query : 'N/A'
        ];
    }
}