<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFeedback extends Model
{
    use HasFactory;

    protected $table = 'user_feedback';

    protected $fillable = [
        'user_id',
        'rating',
        'feedback_type',
        'message',
        'admin_response',  // نیا field add کیا
        'contact_permission',
        'user_agent',
        'ip_address',
        'status'
    ];

    protected $casts = [
        'contact_permission' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('feedback_type', $type);
    }

    public function scopeHighRated($query)
    {
        return $query->where('rating', '>=', 4);
    }

    public function scopeLowRated($query)
    {
        return $query->where('rating', '<=', 2);
    }

    // نیا scope add کیا
    public function scopeWithResponse($query)
    {
        return $query->whereNotNull('admin_response');
    }

    public function scopeWithoutResponse($query)
    {
        return $query->whereNull('admin_response');
    }

    // Accessors
    public function getFeedbackTypeNameAttribute()
    {
        $types = [
            'suggestion' => 'Suggestion',
            'bug' => 'Bug Report',
            'feature' => 'Feature Request',
            'general' => 'General Feedback'
        ];

        return $types[$this->feedback_type] ?? 'Unknown';
    }

    public function getRatingTextAttribute()
    {
        $ratings = [
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Very Good',
            5 => 'Excellent'
        ];

        return $ratings[$this->rating] ?? 'Unknown';
    }

    // نئے accessors add کیے
    public function getStatusBadgeColorAttribute()
    {
        $colors = [
            'pending' => 'orange',
            'reviewed' => 'blue',
            'resolved' => 'green'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getFeedbackTypeIconAttribute()
    {
        $icons = [
            'suggestion' => 'lightbulb',
            'bug' => 'bug',
            'feature' => 'plus-circle',
            'general' => 'comment'
        ];

        return $icons[$this->feedback_type] ?? 'comment';
    }

    public function getFeedbackTypeColorAttribute()
    {
        $colors = [
            'suggestion' => 'yellow',
            'bug' => 'red',
            'feature' => 'green',
            'general' => 'blue'
        ];

        return $colors[$this->feedback_type] ?? 'blue';
    }

    // Helper methods
    public function hasAdminResponse()
    {
        return !empty($this->admin_response);
    }

    public function markAsReviewed()
    {
        return $this->update(['status' => 'reviewed']);
    }

    public function markAsResolved()
    {
        return $this->update(['status' => 'resolved']);
    }
}