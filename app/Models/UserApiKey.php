<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class UserApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'api_provider',
        'api_key',
        'key_name',
        'google_email',
        'is_active',
        'is_valid',
        'usage_count',
        'daily_limit',
        'monthly_limit',
        'last_used',
        'error_count',
        'last_error'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_valid' => 'boolean',
        'last_used' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Encrypt API key before saving
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    // Decrypt API key when retrieving
    public function getApiKeyAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    // Get masked API key for display
    public function getMaskedApiKeyAttribute()
    {
        $key = $this->api_key;
        if (!$key) return '••••••••••••••••••••••••••••••••';
        
        $length = strlen($key);
        if ($length <= 8) return str_repeat('•', $length);
        
        return substr($key, 0, 6) . str_repeat('•', $length - 10) . substr($key, -4);
    }

    // Relationship with User (owner of the API key)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with assigned users (for company keys)
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'api_key_user_assignments', 'api_key_id', 'user_id')
                    ->withTimestamps()
                    ->withPivot('assigned_at');
    }

    // Check if API key is working
    public function isWorking()
    {
        return $this->is_active && $this->is_valid;
    }

    // Update usage count
    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used' => now()]);
    }

    // Record error
    public function recordError($error)
    {
        $this->increment('error_count');
        $this->update(['last_error' => $error]);
    }

    // Reset daily usage (run daily via cron)
    public static function resetDailyUsage()
    {
        static::query()->update(['usage_count' => 0]);
    }
}