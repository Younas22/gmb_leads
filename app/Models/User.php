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
    const TYPE_COMPANY = 'company';

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

    // Check if user is company
    public function isCompany()
    {
        return $this->user_type === self::TYPE_COMPANY;
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
     * Get the subscriptions for the user.
     */
    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Subscription::class);
    }

    /**
     * Check if user has restricted access (no active subscription)
     * Users without any active subscription can only access subscription page
     */
    public function hasRestrictedAccess()
    {
        // Admin users are never restricted
        if ($this->isAdmin()) {
            return false;
        }

        $hasActiveSubscription = $this->subscriptions()
            ->where('status', 'active')
            ->exists();

        // Restrict access if user has no active subscription
        return !$hasActiveSubscription;
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

    /**
     * Get the active subscription for the user.
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where(function($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            })
            ->with('package.features')
            ->first();
    }

    /**
     * Get a specific feature value from user's active subscription package.
     *
     * @param string $featureKey
     * @param mixed $default
     * @return mixed
     */
    public function getFeatureValue($featureKey, $default = null)
    {
        $subscription = $this->activeSubscription();

        if (!$subscription || !$subscription->package) {
            return $default;
        }

        $feature = $subscription->package->features
            ->where('feature_key', $featureKey)
            ->first();

        if (!$feature) {
            return $default;
        }

        // If unlimited, return -1 or true based on context
        if ($feature->is_unlimited) {
            return -1; // -1 means unlimited
        }

        return $feature->feature_value;
    }

    /**
     * Check if user has access to a boolean feature.
     *
     * @param string $featureKey
     * @return bool
     */
    public function hasFeature($featureKey)
    {
        $value = $this->getFeatureValue($featureKey, false);

        // -1 means unlimited
        if ($value === -1) {
            return true;
        }

        // Check for boolean-like values
        return $value === true || $value === 'true' || $value === '1' || $value === 1;
    }

    /**
     * Get the limit for a numeric feature.
     *
     * @param string $featureKey
     * @return int (-1 for unlimited, 0 for not allowed, positive for limit)
     */
    public function getFeatureLimit($featureKey)
    {
        $value = $this->getFeatureValue($featureKey, 0);

        // Already -1 for unlimited
        if ($value === -1) {
            return -1;
        }

        return (int) $value;
    }

    /**
     * Check if user can perform an action based on current usage vs limit.
     *
     * @param string $featureKey
     * @param int $currentUsage
     * @return bool
     */
    public function canUseFeature($featureKey, $currentUsage)
    {
        $limit = $this->getFeatureLimit($featureKey);

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        return $currentUsage < $limit;
    }

    /**
     * Get API keys for the user.
     */
    public function apiKeys()
    {
        return $this->hasMany(\App\Models\UserApiKey::class);
    }

    /**
     * Check if user can add more API keys.
     *
     * @return bool
     */
    public function canAddApiKey()
    {
        $limit = $this->getFeatureLimit('api_limit');

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        $currentCount = $this->apiKeys()->count();

        return $currentCount < $limit;
    }

    /**
     * Get remaining API key slots.
     *
     * @return int|string (-1 or 'unlimited' for unlimited, otherwise remaining count)
     */
    public function getRemainingApiKeySlots()
    {
        $limit = $this->getFeatureLimit('api_limit');

        if ($limit === -1) {
            return 'unlimited';
        }

        $currentCount = $this->apiKeys()->count();

        return max(0, $limit - $currentCount);
    }

}