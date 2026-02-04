<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use App\Models\SavedLead;
use App\Models\SearchHistory;
use App\Models\UserTutorialProgress;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'password',
        'plain_password',
        'avatar',
        'google_id',
        'email_verified',
        'email_verification_token',
        'password_reset_token',
        'password_reset_expires',
        'login_type',
        'status',
        'user_type',
        'last_login',
        'preferences',
        'welcome_tutorial_seen',
        'welcome_tutorial_seen_at',
        'email_verified_at',
        'email_verification_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'password_reset_token',
        'email_verification_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_reset_expires' => 'datetime',
            'last_login' => 'datetime',
            'email_verified' => 'boolean',
        ];
    }

    // User type constants
    const TYPE_USER = 'user';
    const TYPE_ADMIN = 'admin';

    // Login type constants
    const LOGIN_REGULAR = 'custom';
    const LOGIN_GOOGLE = 'google';

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    // Check if user is admin
    public function isAdmin()
    {
        return $this->user_type === self::TYPE_ADMIN;
    }

    // Check if user is regular user
    public function isUser()
    {
        return $this->user_type === self::TYPE_USER;
    }

    // Create user from Google data
    public static function createFromGoogle(SocialiteUser $googleUser)
    {
        $nameParts = explode(' ', $googleUser->getName(), 2);
        
        return self::create([
            'first_name' => $nameParts[0] ?? '',
            'last_name' => $nameParts[1] ?? '',
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'login_type' => self::LOGIN_GOOGLE,
            'user_type' => self::TYPE_USER,
            'status' => self::STATUS_ACTIVE,
            'email_verified' => true,
            'last_login' => now(),
        ]);
    }

    // Update last login
    public function updateLastLogin()
    {
        $this->update(['last_login' => now()]);
    }

    /**
     * Get the saved leads for the user.
     */
    public function savedLeads()
    {
        return $this->hasMany(SavedLead::class);
    }

    /**
     * Get the search histories for the user.
     */
    public function searchHistories()
    {
        return $this->hasMany(SearchHistory::class);
    }

    /**
     * Get user preferences as array
     */
    public function getPreferencesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set user preferences as JSON
     */
    public function setPreferencesAttribute($value)
    {
        $this->attributes['preferences'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get user's full name
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: $this->name;
    }

    /**
     * Get user initials for avatar
     */
    public function getInitialsAttribute()
    {
        $firstName = $this->first_name ?? $this->name;
        $lastName = $this->last_name ?? '';
        
        return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    }

    /**
     * Get avatar URL or default
     */
    public function getAvatarUrlAttribute()
    {
        return $this->avatar ?? '/images/default-avatar.png';
    }


        /**
     * Get the tutorial progress for the user.
     */
    public function tutorialProgress()
    {
        return $this->hasMany(UserTutorialProgress::class);
    }

    /**
     * Get completed tutorials for the user.
     */
    public function completedTutorials()
    {
        return $this->tutorialProgress()->where('completed', true);
    }

    /**
     * Check if user has completed a specific tutorial
     */
    public function hasCompletedTutorial($tutorialKey)
    {
        return $this->tutorialProgress()
            ->where('tutorial_key', $tutorialKey)
            ->where('completed', true)
            ->exists();
    }

    /**
     * Get tutorial completion percentage
     */
    public function getTutorialCompletionPercentage($totalTutorials = 7)
    {
        $completed = $this->completedTutorials()->count();
        return $totalTutorials > 0 ? round(($completed / $totalTutorials) * 100) : 0;
    }

    /**
     * Get total tutorial watch time in minutes
     */
    public function getTotalWatchTime()
    {
        return round($this->tutorialProgress()->sum('watch_time') / 60);
    }

        public function hasSeenWelcomeTutorial()
    {
        return $this->welcome_tutorial_seen;
    }

    /**
     * Mark welcome tutorial as seen
     */
    public function markWelcomeTutorialAsSeen()
    {
        $this->update([
            'welcome_tutorial_seen' => true,
            'welcome_tutorial_seen_at' => now(),
        ]);
    }

}