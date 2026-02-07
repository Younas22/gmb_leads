<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EmailTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'subject',
        'body',
        'default_body',
        'default_subject',
        'available_variables',
    ];

    protected $casts = [
        'available_variables' => 'array',
    ];

    /**
     * Get template by slug with caching
     */
    public static function getBySlug($slug)
    {
        return Cache::remember("email_template_{$slug}", 3600, function () use ($slug) {
            return self::where('slug', $slug)->first();
        });
    }

    /**
     * Replace placeholders in body with actual data
     */
    public function renderBody($data = [])
    {
        $content = $this->body;

        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $content = str_replace('{' . $key . '}', $value, $content);
            }
        }

        return $content;
    }

    /**
     * Replace placeholders in subject with actual data
     */
    public function renderSubject($data = [])
    {
        $subject = $this->subject;

        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $subject = str_replace('{' . $key . '}', $value, $subject);
            }
        }

        return $subject;
    }

    /**
     * Reset template to default content
     */
    public function resetToDefault()
    {
        $this->update([
            'subject' => $this->default_subject,
            'body' => $this->default_body,
        ]);

        Cache::forget("email_template_{$this->slug}");

        return $this;
    }

    /**
     * Clear cache when saving
     */
    protected static function booted()
    {
        static::saved(function ($template) {
            Cache::forget("email_template_{$template->slug}");
        });
    }
}
