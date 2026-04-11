<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AdminApiKey extends Model
{
    protected $fillable = [
        'key_name',
        'api_key',
        'status',
        'text_search_price',
        'details_price',
        'text_search_count',
        'details_count',
        'text_search_total_cost',
        'details_total_cost',
        'last_used_at',
    ];

    protected $casts = [
        'text_search_price' => 'decimal:4',
        'details_price' => 'decimal:4',
        'text_search_count' => 'integer',
        'details_count' => 'integer',
        'text_search_total_cost' => 'decimal:4',
        'details_total_cost' => 'decimal:4',
        'last_used_at' => 'datetime',
    ];

    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    public function getApiKeyAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getMaskedApiKeyAttribute()
    {
        $key = $this->api_key;
        if (!$key) return str_repeat('*', 20);
        $length = strlen($key);
        if ($length <= 8) return str_repeat('*', $length);
        return substr($key, 0, 6) . str_repeat('*', $length - 10) . substr($key, -4);
    }

    public function scopeActive($query)
    {
        // If extension mode is enabled, disable all APIs
        if (\App\Models\Setting::get('extension_mode', false)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('status', 'active');
    }

    public function incrementTextSearch()
    {
        $this->increment('text_search_count');
        $this->text_search_total_cost = $this->text_search_count * $this->text_search_price;
        $this->last_used_at = now();
        $this->save();
    }

    public function incrementDetails()
    {
        $this->increment('details_count');
        $this->details_total_cost = $this->details_count * $this->details_price;
        $this->last_used_at = now();
        $this->save();
    }

    public function recordApiUsage($textSearchCalls, $detailsCalls)
    {
        $this->text_search_count += $textSearchCalls;
        $this->details_count += $detailsCalls;
        $this->text_search_total_cost = $this->text_search_count * $this->text_search_price;
        $this->details_total_cost = $this->details_count * $this->details_price;
        $this->last_used_at = now();
        $this->save();
    }

    public function getTotalCostAttribute()
    {
        return $this->text_search_total_cost + $this->details_total_cost;
    }

    public function getTotalCallsAttribute()
    {
        return $this->text_search_count + $this->details_count;
    }
}
