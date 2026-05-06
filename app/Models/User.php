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
        'company_id',
        'last_login',
        'preferences',
        'welcome_tutorial_seen',
        'welcome_tutorial_seen_at',
        'email_verified_at',
        'email_verification_token',
        'signups_enabled',
        'extension_token',
        'referral_code',
        'referred_by',
        'affiliate_active',
        'custom_commission_type',
        'custom_commission_value',
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
            'signups_enabled' => 'boolean',
            'affiliate_active' => 'boolean',
            'custom_commission_value' => 'decimal:2',
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

    // Check if user is a team member (belongs to a company)
    public function isTeamMember()
    {
        return !empty($this->company_id);
    }

    // Check if company allows new signups
    public function allowsNewSignups()
    {
        // Only relevant for company accounts
        if (!$this->isCompany()) {
            return true;
        }

        return $this->signups_enabled;
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

        // Team members inherit access from their company
        if ($this->isTeamMember() && $this->company) {
            return $this->company->hasRestrictedAccess();
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
        // Team members inherit subscription from their company
        if ($this->isTeamMember() && $this->company) {
            return $this->company->activeSubscription();
        }

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

        // If unlimited flag set or value is 'unlimited' string, return -1
        if ($feature->is_unlimited || $feature->feature_value === 'unlimited') {
            return -1;
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

    // ──────────────────────────────────────────────
    // Credits System (API Calls Tracking)
    // ──────────────────────────────────────────────

    /**
     * Get the account owner (resolves team members to their company owner).
     */
    public function getAccountOwner()
    {
        return $this->isTeamMember() ? $this->company : $this;
    }

    /**
     * Get all user IDs that share the quota (company + team members).
     */
    public function getQuotaUserIds()
    {
        $accountOwner = $this->getAccountOwner();
        $userIds = [$accountOwner->id];
        if ($accountOwner->isCompany()) {
            $teamMemberIds = $accountOwner->teamMembers()->pluck('id')->toArray();
            $userIds = array_merge($userIds, $teamMemberIds);
        }
        return $userIds;
    }

    /**
     * Get the number of leads saved today (used against daily_leads_limit).
     */
    public function getCreditsUsed()
    {
        $userIds = $this->getQuotaUserIds();

        return \App\Models\SavedLead::whereIn('user_id', $userIds)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Get the daily leads limit from the user's package.
     * Returns -1 for unlimited, 0 if no subscription.
     */
    public function getCreditLimit()
    {
        return $this->getFeatureLimit('daily_leads_limit');
    }

    /**
     * Get remaining credits for the current billing month.
     */
    public function getRemainingCredits()
    {
        $limit = $this->getCreditLimit();

        if ($limit === -1) {
            return 'unlimited';
        }

        $used = $this->getCreditsUsed();
        return max(0, $limit - $used);
    }

    /**
     * Check if user has credits remaining.
     */
    public function hasCredits()
    {
        $limit = $this->getCreditLimit();

        if ($limit === -1) {
            return true;
        }

        return $this->getCreditsUsed() < $limit;
    }

    /**
     * Record credits (API calls) used in api_usages table.
     *
     * @param int $credits Number of API calls consumed (text_search + place_details)
     */
    public function recordCreditsUsed($credits)
    {
        if ($credits <= 0) {
            return null;
        }

        $accountOwner = $this->getAccountOwner();

        $usage = \App\Models\ApiUsage::firstOrCreate(
            [
                'user_id' => $this->id,
                'company_id' => $accountOwner->id !== $this->id ? $accountOwner->id : null,
                'date' => now()->toDateString(),
            ],
            [
                'searches_used' => 0,
                'leads_fetched' => 0,
            ]
        );

        $usage->increment('searches_used', $credits);

        return $usage;
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

        // Count API keys for company + all team members
        $accountOwner = $this->isTeamMember() ? $this->company : $this;
        $userIds = [$accountOwner->id];
        if ($accountOwner->isCompany()) {
            $teamMemberIds = $accountOwner->teamMembers()->pluck('id')->toArray();
            $userIds = array_merge($userIds, $teamMemberIds);
        }

        $currentCount = \App\Models\UserApiKey::whereIn('user_id', $userIds)->count();

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

        // Count API keys for company + all team members
        $accountOwner = $this->isTeamMember() ? $this->company : $this;
        $userIds = [$accountOwner->id];
        if ($accountOwner->isCompany()) {
            $teamMemberIds = $accountOwner->teamMembers()->pluck('id')->toArray();
            $userIds = array_merge($userIds, $teamMemberIds);
        }

        $currentCount = \App\Models\UserApiKey::whereIn('user_id', $userIds)->count();

        return max(0, $limit - $currentCount);
    }

    /**
     * Get the company this user belongs to.
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    /**
     * Get team members for this company.
     */
    public function teamMembers()
    {
        return $this->hasMany(User::class, 'company_id');
    }

    /**
     * Check if user can add more team members.
     *
     * @return bool
     */
    public function canAddTeamMember()
    {
        // Only company accounts can have team members
        if (!$this->isCompany()) {
            return false;
        }

        $limit = $this->getFeatureLimit('team_members');

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        // No limit in package
        if ($limit === 0) {
            return false;
        }

        $currentCount = $this->teamMembers()->count();

        return $currentCount < $limit;
    }

    /**
     * Get remaining team member slots.
     *
     * @return int|string (-1 or 'unlimited' for unlimited, otherwise remaining count)
     */
    public function getRemainingTeamMemberSlots()
    {
        if (!$this->isCompany()) {
            return 0;
        }

        $limit = $this->getFeatureLimit('team_members');

        if ($limit === -1) {
            return 'unlimited';
        }

        if ($limit === 0) {
            return 0;
        }

        $currentCount = $this->teamMembers()->count();

        return max(0, $limit - $currentCount);
    }

    /**
     * Get team members count.
     *
     * @return int
     */
    public function getTeamMembersCount()
    {
        if (!$this->isCompany()) {
            return 0;
        }

        return $this->teamMembers()->count();
    }

    // ──────────────────────────────────────────────
    // Affiliate / Referral System
    // ──────────────────────────────────────────────

    public function affiliateClicks()
    {
        return $this->hasMany(\App\Models\AffiliateClick::class, 'referral_code', 'referral_code');
    }

    public function affiliateConversions()
    {
        return $this->hasMany(\App\Models\AffiliateConversion::class, 'referrer_id');
    }

    public function affiliateEarning()
    {
        return $this->hasOne(\App\Models\AffiliateEarning::class);
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(\App\Models\WithdrawalRequest::class);
    }

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by', 'referral_code');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by', 'referral_code');
    }

    public function getReferralLink(): string
    {
        return url('/') . '?ref=' . $this->referral_code;
    }

    public function getOrCreateEarning(): \App\Models\AffiliateEarning
    {
        return $this->affiliateEarning()->firstOrCreate(
            ['user_id' => $this->id],
            ['total_earned' => 0, 'pending' => 0, 'approved' => 0, 'withdrawn' => 0]
        );
    }

}