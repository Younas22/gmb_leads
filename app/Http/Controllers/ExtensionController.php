<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\UserExtensionDevice;
use App\Models\SavedLead;
use App\Models\SearchHistory;
use App\Models\Country;
use App\Models\State;
use App\Models\City;

class ExtensionController extends Controller
{
    // ──────────────────────────────────────────────
    // Helper: get authenticated user from token
    // ──────────────────────────────────────────────

    private function getUserFromToken(Request $request): ?User
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }
        return User::where('extension_token', $token)
                   ->where('status', 'active')
                   ->first();
    }

    // ──────────────────────────────────────────────
    // POST /api/extension/login
    // Body: { email, password }
    // Returns: { token, user: { name, email, package, credits_used, credits_limit, device_limit } }
    // ──────────────────────────────────────────────

    public function login(Request $request)
    {
        Log::channel('daily')->info('EXT_LOGIN_ATTEMPT', [
            'email' => $request->email,
            'ip'    => $request->ip(),
        ]);

        try {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('daily')->warning('EXT_LOGIN_VALIDATION_FAILED', [
                'email'  => $request->email,
                'errors' => $e->errors(),
            ]);
            return response()->json(['error' => 'Validation failed.', 'details' => $e->errors()], 422);
        }

        // Find user by email (ignore status first so we can log exact failure)
        $userByEmail = User::where('email', $request->email)->first();

        if (!$userByEmail) {
            Log::channel('daily')->warning('EXT_LOGIN_FAILED_NO_USER', ['email' => $request->email]);
            return response()->json(['error' => 'Invalid email or password.'], 401);
        }

        if ($userByEmail->status !== 'active') {
            Log::channel('daily')->warning('EXT_LOGIN_FAILED_INACTIVE', [
                'email'  => $request->email,
                'status' => $userByEmail->status,
            ]);
            return response()->json(['error' => 'Your account is not active.'], 401);
        }

        if (!Hash::check($request->password, $userByEmail->password)) {
            Log::channel('daily')->warning('EXT_LOGIN_FAILED_WRONG_PASSWORD', ['email' => $request->email]);
            return response()->json(['error' => 'Invalid email or password.'], 401);
        }

        $user = $userByEmail;

        if ($user->isAdmin()) {
            Log::channel('daily')->warning('EXT_LOGIN_FAILED_ADMIN', ['email' => $request->email]);
            return response()->json(['error' => 'Admin accounts cannot use the extension.'], 403);
        }

        if ($user->isTeamMember()) {
            Log::channel('daily')->warning('EXT_LOGIN_FAILED_TEAM_MEMBER', ['email' => $request->email]);
        }

        $hasRestricted = $user->hasRestrictedAccess();
        if ($hasRestricted) {
            $subscription = $user->subscriptions()->orderBy('created_at', 'desc')->first();
            Log::channel('daily')->warning('EXT_LOGIN_FAILED_NO_SUBSCRIPTION', [
                'email'             => $request->email,
                'user_id'           => $user->id,
                'last_subscription' => $subscription ? [
                    'id'     => $subscription->id,
                    'status' => $subscription->status,
                    'plan'   => $subscription->package?->name,
                ] : null,
            ]);
            return response()->json(['error' => 'No active subscription found. Please subscribe to use the extension.'], 403);
        }

        // Generate or reuse token
        if (!$user->extension_token) {
            $user->extension_token = Str::random(64);
            $user->save();
        }

        try {
            $deviceLimit   = $user->getFeatureLimit('max_devices');
            $creditLimit   = $user->getCreditLimit();
            $creditsUsed   = $user->getCreditsUsed();
            $packageSlug   = $user->activeSubscription()?->package?->slug ?? 'starter';
            $packageName   = $user->activeSubscription()?->package?->name ?? 'Starter';
        } catch (\Exception $e) {
            Log::channel('daily')->error('EXT_LOGIN_FEATURE_LOAD_ERROR', [
                'email'   => $request->email,
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Server error loading plan details.'], 500);
        }

        Log::channel('daily')->info('EXT_LOGIN_SUCCESS', [
            'email'        => $user->email,
            'user_id'      => $user->id,
            'package'      => $packageName,
            'credit_limit' => $creditLimit,
            'device_limit' => $deviceLimit,
        ]);

        return response()->json([
            'token' => $user->extension_token,
            'user'  => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'package_slug'   => $packageSlug,
                'package_name'   => $packageName,
                'credits_used'   => $creditsUsed,
                'credits_limit'  => $creditLimit,
                'device_limit'   => $deviceLimit,
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    // GET /extension/web-token
    // Uses web session (cookie auth) — no token needed
    // Called by the extension to auto-login when the
    // user is already logged into the web app.
    // ──────────────────────────────────────────────

    public function webAutoLogin(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        Log::channel('daily')->info('EXT_WEB_AUTOLOGIN_ATTEMPT', [
            'ip'           => $request->ip(),
            'user_id'      => $user?->id,
            'email'        => $user?->email,
            'is_logged_in' => (bool) $user,
        ]);

        if (!$user) {
            Log::channel('daily')->warning('EXT_WEB_AUTOLOGIN_FAILED_NO_SESSION', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Not authenticated.'], 401);
        }

        if ($user->isAdmin()) {
            Log::channel('daily')->warning('EXT_WEB_AUTOLOGIN_FAILED_ADMIN', ['email' => $user->email]);
            return response()->json(['error' => 'Admin accounts cannot use the extension.'], 403);
        }

        if ($user->hasRestrictedAccess()) {
            $subscription = $user->subscriptions()->orderBy('created_at', 'desc')->first();
            Log::channel('daily')->warning('EXT_WEB_AUTOLOGIN_FAILED_NO_SUBSCRIPTION', [
                'email'             => $user->email,
                'user_id'           => $user->id,
                'last_subscription' => $subscription ? [
                    'id'     => $subscription->id,
                    'status' => $subscription->status,
                    'plan'   => $subscription->package?->name,
                ] : null,
            ]);
            return response()->json(['error' => 'No active subscription.'], 403);
        }

        // Generate token if not set
        if (!$user->extension_token) {
            $user->extension_token = \Illuminate\Support\Str::random(64);
            $user->save();
        }

        try {
            $deviceLimit = $user->getFeatureLimit('max_devices');
            $creditLimit = $user->getCreditLimit();
            $creditsUsed = $user->getCreditsUsed();
            $packageName = $user->activeSubscription()?->package?->name ?? 'Starter';
        } catch (\Exception $e) {
            Log::channel('daily')->error('EXT_WEB_AUTOLOGIN_FEATURE_LOAD_ERROR', [
                'email'   => $user->email,
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Server error loading plan details.'], 500);
        }

        Log::channel('daily')->info('EXT_WEB_AUTOLOGIN_SUCCESS', [
            'email'        => $user->email,
            'user_id'      => $user->id,
            'package'      => $packageName,
            'credit_limit' => $creditLimit,
            'device_limit' => $deviceLimit,
        ]);

        return response()->json([
            'token' => $user->extension_token,
            'user'  => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'package_name'  => $packageName,
                'credits_used'  => $creditsUsed,
                'credits_limit' => $creditLimit,
                'device_limit'  => $deviceLimit,
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    // POST /api/extension/logout
    // Header: Authorization: Bearer {token}
    // Revokes the token
    // ──────────────────────────────────────────────

    public function logout(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user->extension_token = null;
        $user->save();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    // ──────────────────────────────────────────────
    // POST /api/extension/register-device
    // Header: Authorization: Bearer {token}
    // Body: { device_fingerprint, device_name }
    // Registers device if under limit
    // ──────────────────────────────────────────────

    public function registerDevice(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'device_fingerprint' => 'required|string|max:255',
            'device_name'        => 'nullable|string|max:255',
        ]);

        $fingerprint = $request->device_fingerprint;

        // If this device is already registered to this user, just touch it
        $existing = UserExtensionDevice::where('user_id', $user->id)
                                       ->where('device_fingerprint', $fingerprint)
                                       ->first();

        if ($existing) {
            $existing->update([
                'is_active'    => true,
                'last_seen_at' => now(),
                'device_name'  => $request->device_name ?? $existing->device_name,
            ]);

            return response()->json([
                'message'   => 'Device already registered.',
                'device_id' => $existing->id,
            ]);
        }

        // Check device limit
        $deviceLimit  = $user->getFeatureLimit('max_devices');

        if ($deviceLimit !== -1) {
            $activeDevices = UserExtensionDevice::where('user_id', $user->id)
                                                ->where('is_active', true)
                                                ->count();

            if ($activeDevices >= $deviceLimit) {
                return response()->json([
                    'error'        => "Device limit reached. Your plan allows {$deviceLimit} device(s). Please remove an existing device from your account settings.",
                    'device_limit' => $deviceLimit,
                    'devices_used' => $activeDevices,
                ], 403);
            }
        }

        // Check if fingerprint belongs to another user (shouldn't happen normally)
        $otherUser = UserExtensionDevice::where('device_fingerprint', $fingerprint)
                                        ->where('user_id', '!=', $user->id)
                                        ->exists();

        if ($otherUser) {
            return response()->json(['error' => 'This device is already registered to another account.'], 409);
        }

        $device = UserExtensionDevice::create([
            'user_id'            => $user->id,
            'device_fingerprint' => $fingerprint,
            'device_name'        => $request->device_name ?? 'Unknown Device',
            'last_seen_at'       => now(),
            'is_active'          => true,
        ]);

        return response()->json([
            'message'   => 'Device registered successfully.',
            'device_id' => $device->id,
        ], 201);
    }

    // ──────────────────────────────────────────────
    // GET /api/extension/devices
    // Header: Authorization: Bearer {token}
    // Returns list of user's registered devices
    // ──────────────────────────────────────────────

    public function listDevices(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $devices = UserExtensionDevice::where('user_id', $user->id)
                                      ->where('is_active', true)
                                      ->orderBy('last_seen_at', 'desc')
                                      ->get(['id', 'device_name', 'device_fingerprint', 'last_seen_at', 'created_at']);

        $deviceLimit = $user->getFeatureLimit('max_devices');

        return response()->json([
            'devices'      => $devices,
            'devices_used' => $devices->count(),
            'device_limit' => $deviceLimit,
        ]);
    }

    // ──────────────────────────────────────────────
    // DELETE /api/extension/devices/{id}
    // Header: Authorization: Bearer {token}
    // Removes a device (deactivates it)
    // ──────────────────────────────────────────────

    public function removeDevice(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $device = UserExtensionDevice::where('id', $id)
                                     ->where('user_id', $user->id)
                                     ->first();

        if (!$device) {
            return response()->json(['error' => 'Device not found.'], 404);
        }

        $device->delete();

        return response()->json(['message' => 'Device removed successfully.']);
    }

    // ──────────────────────────────────────────────
    // GET /api/extension/status
    // Header: Authorization: Bearer {token}
    // Query: ?device_fingerprint=xxx
    // Returns user subscription + credit status
    // ──────────────────────────────────────────────

    public function status(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Verify device if fingerprint provided
        if ($request->filled('device_fingerprint')) {
            $deviceRegistered = UserExtensionDevice::where('user_id', $user->id)
                                                   ->where('device_fingerprint', $request->device_fingerprint)
                                                   ->where('is_active', true)
                                                   ->exists();

            if (!$deviceRegistered) {
                return response()->json(['error' => 'This device is not registered. Please register it first.'], 403);
            }

            // Touch last_seen
            UserExtensionDevice::where('user_id', $user->id)
                                ->where('device_fingerprint', $request->device_fingerprint)
                                ->update(['last_seen_at' => now()]);
        }

        $creditLimit  = $user->getCreditLimit();
        $creditsUsed  = $user->getCreditsUsed();
        $deviceLimit  = $user->getFeatureLimit('max_devices');
        $packageName  = $user->activeSubscription()?->package?->name ?? 'Starter';
        $packageSlug  = $user->activeSubscription()?->package?->slug ?? 'starter';
        $subscription = $user->activeSubscription();

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'subscription' => [
                'package_name'   => $packageName,
                'package_slug'   => $packageSlug,
                'status'         => $subscription?->status ?? 'none',
                'expires_at'     => $subscription?->end_date?->toDateString(),
            ],
            'credits' => [
                'used'      => $creditsUsed,
                'limit'     => $creditLimit,   // -1 = unlimited
                'remaining' => $creditLimit === -1 ? 'unlimited' : max(0, $creditLimit - $creditsUsed),
                'has_credits' => $user->hasCredits(),
            ],
            'devices' => [
                'limit' => $deviceLimit,
                'used'  => UserExtensionDevice::where('user_id', $user->id)->where('is_active', true)->count(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    // POST /api/extension/save-leads
    // Header: Authorization: Bearer {token}
    // Body: { device_fingerprint, leads: [...], search_data: {...} }
    // Saves scraped leads + records credits used
    // ──────────────────────────────────────────────

    public function saveLeads(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'device_fingerprint' => 'required|string',
            'leads'              => 'required|array|min:1',
            'leads.*'            => 'required|array',
            'search_data'        => 'nullable|array',
        ]);

        // Verify device is registered
        $device = UserExtensionDevice::where('user_id', $user->id)
                                     ->where('device_fingerprint', $request->device_fingerprint)
                                     ->where('is_active', true)
                                     ->first();

        if (!$device) {
            return response()->json(['error' => 'This device is not registered. Please register it first.'], 403);
        }

        $device->touchSeen();

        // Daily leads limit check
        $dailyLimit  = $user->getCreditLimit();   // -1 = unlimited, 0 = no plan, N = limit
        $usedToday   = $user->getCreditsUsed();

        if ($dailyLimit !== -1 && $usedToday >= $dailyLimit) {
            return response()->json([
                'error'       => "Daily limit reached. You have used {$usedToday}/{$dailyLimit} leads today. Upgrade your plan or try again tomorrow.",
                'used'        => $usedToday,
                'limit'       => $dailyLimit,
                'remaining'   => 0,
                'reset_at'    => now()->endOfDay()->toDateTimeString(),
            ], 429);
        }

        // How many slots are still available today
        $slotsRemaining = $dailyLimit === -1 ? PHP_INT_MAX : ($dailyLimit - $usedToday);

        $leads      = $request->leads;
        $searchData = $request->input('search_data', []);

        // Resolve country/state/city names to IDs for filter compatibility
        // Extension may send separate fields OR only location_name as full string
        $countryName = $searchData['country_name'] ?? $searchData['country'] ?? null;
        $stateName   = $searchData['state_name']   ?? $searchData['state']   ?? null;
        $cityName    = $searchData['city_name']     ?? $searchData['city']    ?? null;

        // Fallback: parse location from location_name string (e.g. "electrician in Alaska United States")
        if (!$countryName && !$stateName && !$cityName) {
            $locationStr = $searchData['location_name'] ?? null;
            if ($locationStr) {
                // Extract part after " in " keyword if present
                if (stripos($locationStr, ' in ') !== false) {
                    $locationStr = trim(substr($locationStr, stripos($locationStr, ' in ') + 4));
                }
                [$countryName, $stateName, $cityName] = $this->parseLocationString($locationStr);
            }
        }

        $countryId = null;
        $stateId   = null;
        $cityId    = null;

        if ($countryName) {
            $countryObj = Country::where('name', $countryName)->first();
            $countryId  = $countryObj?->id;
        }
        if ($stateName && $countryId) {
            $stateObj = State::where('name', $stateName)->where('country_id', $countryId)->first();
            $stateId  = $stateObj?->id;
        }
        if ($cityName && $stateId) {
            $cityObj = City::where('name', $cityName)->where('state_id', $stateId)->first();
            $cityId  = $cityObj?->id;
        }

        \Illuminate\Support\Facades\Log::info('EXT_LOCATION_DEBUG', [
            'search_data_raw'  => $searchData,
            'country_name'     => $countryName,
            'state_name'       => $stateName,
            'city_name'        => $cityName,
            'country_id_found' => $countryId,
            'state_id_found'   => $stateId,
            'city_id_found'    => $cityId,
        ]);

        $savedCount     = 0;
        $duplicateCount = 0;
        $limitSkipped   = 0;

        foreach ($leads as $leadData) {
            $placeId = $leadData['profile'] ?? null;
            if ($placeId && str_contains($placeId, 'maps.google.com')) {
                // Try !19s format: !19sChIJxxx
                if (preg_match('/!19s(ChIJ[^?&!]+)/', $placeId, $m)) {
                    $placeId = urldecode($m[1]);
                } elseif (preg_match('/cid=(\d+)/', $placeId, $m)) {
                    $placeId = $m[1];
                } else {
                    // Fallback: truncate to 500 chars
                    $placeId = substr($placeId, 0, 500);
                }
            }

            $exists = SavedLead::existsForUser(
                $user->id,
                $placeId,
                $leadData['name'] ?? null,
                $leadData['address'] ?? null
            );

            if ($exists) {
                $duplicateCount++;
                continue;
            }

            // Stop saving once daily slot limit is reached mid-batch
            if ($savedCount >= $slotsRemaining) {
                $limitSkipped++;
                continue;
            }

            // Determine latest review date
            $lastReviewDate  = null;
            $reviewsSample   = isset($leadData['reviews']) ? array_slice($leadData['reviews'], 0, 3) : [];

            $rawDate = $leadData['latest_review_date'] ?? null;
            if (!empty($rawDate)) {
                if (is_numeric($rawDate)) {
                    $lastReviewDate = date('Y-m-d H:i:s', $rawDate);
                } else {
                    // Parse relative strings like "3 months ago", "a year ago", "Edited 2 weeks ago"
                    $str = strtolower(trim(preg_replace('/^edited\s+/i', '', $rawDate)));
                    $now = \Carbon\Carbon::now();
                    if (preg_match('/(\d+)\s+year/', $str, $m)) {
                        $lastReviewDate = $now->subYears((int)$m[1])->toDateTimeString();
                    } elseif (str_contains($str, 'a year') || str_contains($str, 'last year')) {
                        $lastReviewDate = $now->subYear()->toDateTimeString();
                    } elseif (preg_match('/(\d+)\s+month/', $str, $m)) {
                        $lastReviewDate = $now->subMonths((int)$m[1])->toDateTimeString();
                    } elseif (str_contains($str, 'a month') || str_contains($str, 'last month')) {
                        $lastReviewDate = $now->subMonth()->toDateTimeString();
                    } elseif (preg_match('/(\d+)\s+week/', $str, $m)) {
                        $lastReviewDate = $now->subWeeks((int)$m[1])->toDateTimeString();
                    } elseif (str_contains($str, 'a week') || str_contains($str, 'last week')) {
                        $lastReviewDate = $now->subWeek()->toDateTimeString();
                    } elseif (preg_match('/(\d+)\s+day/', $str, $m)) {
                        $lastReviewDate = $now->subDays((int)$m[1])->toDateTimeString();
                    } elseif (str_contains($str, 'yesterday')) {
                        $lastReviewDate = $now->subDay()->toDateTimeString();
                    } elseif (str_contains($str, 'today') || str_contains($str, 'just now') || str_contains($str, 'an hour') || str_contains($str, 'hours ago') || str_contains($str, 'minutes ago')) {
                        $lastReviewDate = $now->toDateTimeString();
                    }
                }
            }

            if (!$lastReviewDate && !empty($reviewsSample)) {
                $times = array_filter(array_column($reviewsSample, 'time'));
                if (!empty($times)) {
                    $lastReviewDate = date('Y-m-d H:i:s', max($times));
                }
            }

            // \Illuminate\Support\Facades\Log::info('EXT_LEAD_DEBUG', [
            //     'name'                      => $leadData['name'] ?? null,
            //     'latest_review_date_raw'    => $leadData['latest_review_date'] ?? 'NOT_PRESENT',
            //     'last_review_date_computed' => $lastReviewDate,
            //     'reviews_sample_count'      => count($reviewsSample),
            //     'social_links_raw'          => $leadData['social_links'] ?? 'NOT_PRESENT',
            //     'social_links_type'         => gettype($leadData['social_links'] ?? null),
            //     'social_links_encoded'      => json_encode($leadData['social_links'] ?? []),
            //     'emails'                    => $leadData['emails'] ?? 'NOT_PRESENT',
            //     'phone'                     => $leadData['phone'] ?? 'NOT_PRESENT',
            //     'website'                   => $leadData['website'] ?? 'NOT_PRESENT',
            // ]);

            try {
                $newLead = SavedLead::create([
                    'user_id'             => $user->id,
                    'place_id'            => $placeId,
                    'name'                => $leadData['name'] ?? 'Unknown',
                    'address'             => $leadData['address'] ?? null,
                    'phone'               => $leadData['phone'] ?? null,
                    'website'             => $leadData['website'] ?? null,
                    'email'               => $leadData['emails'][0] ?? null,
                    'latitude'            => isset($leadData['latitude']) ? (float) $leadData['latitude'] : null,
                    'longitude'           => isset($leadData['longitude']) ? (float) $leadData['longitude'] : null,
                    'city'                => $cityId,
                    'state'               => $stateId,
                    'country'             => $countryId,
                    'postal_code'         => null,
                    'category'            => $searchData['query'] ?? null,
                    'rating'              => isset($leadData['rating']) ? (float) $leadData['rating'] : null,
                    'total_reviews'       => isset($leadData['total_reviews']) ? (int) $leadData['total_reviews'] : 0,
                    'last_review_date'    => $lastReviewDate,
                    'opening_hours'       => json_encode($leadData['opening_hours'] ?? []),
                    'google_profile_url'  => $leadData['profile'] ?? null,
                    'social_links'        => json_encode($leadData['social_links'] ?? []),
                    'reviews_sample'      => json_encode($reviewsSample),
                    'search_query'        => $searchData['query'] ?? null,
                    'search_location'     => $searchData['location_name'] ?? null,
                    'search_radius'       => $searchData['radius'] ?? null,
                    'found_via_api'       => 'extension',
                    'contact_status'      => 'not_contacted',
                ]);
                $savedCount++;


            } catch (\Exception $e) {
                \Log::error('Extension save lead error: ' . $e->getMessage(), ['lead' => $leadData]);
            }
        }

        $message = "Successfully saved {$savedCount} lead(s).";
        if ($duplicateCount > 0) {
            $message .= " {$duplicateCount} duplicate(s) skipped.";
        }
        if ($limitSkipped > 0) {
            $message .= " {$limitSkipped} lead(s) skipped — daily limit reached.";
        }

        $usedAfter      = $usedToday + $savedCount;
        $remainingAfter = $dailyLimit === -1 ? 'unlimited' : max(0, $dailyLimit - $usedAfter);

        return response()->json([
            'success'         => true,
            'message'         => $message,
            'saved_count'     => $savedCount,
            'duplicate_count' => $duplicateCount,
            'limit_skipped'   => $limitSkipped,
            'daily_limit'     => $dailyLimit === -1 ? 'unlimited' : $dailyLimit,
            'used_today'      => $usedAfter,
            'remaining_today' => $remainingAfter,
        ]);
    }

    // ──────────────────────────────────────────────
    // Parse a free-text location string into
    // [countryName, stateName, cityName]
    // e.g. "Alaska United States" → ["United States", "Alaska", null]
    // e.g. "New York New York United States" → ["United States", "New York", "New York"]
    // ──────────────────────────────────────────────

    private function parseLocationString(string $location): array
    {
        $location = trim($location);
        $words    = explode(' ', $location);
        $total    = count($words);

        $countryName = null;
        $stateName   = null;
        $cityName    = null;

        // Try matching country from the end (up to 3 words)
        $countryObj  = null;
        $countryLen  = 0;
        for ($len = min(3, $total); $len >= 1; $len--) {
            $candidate = implode(' ', array_slice($words, $total - $len));
            $found = Country::whereRaw('LOWER(name) = ?', [strtolower($candidate)])->first();
            if ($found) {
                $countryObj  = $found;
                $countryName = $found->name;
                $countryLen  = $len;
                break;
            }
        }

        $remaining = array_slice($words, 0, $total - $countryLen);

        if (empty($remaining) || !$countryObj) {
            return [$countryName, $stateName, $cityName];
        }

        // Try matching state from the end of remaining (up to 3 words)
        $stateObj = null;
        $stateLen = 0;
        $remTotal = count($remaining);
        for ($len = min(3, $remTotal); $len >= 1; $len--) {
            $candidate = implode(' ', array_slice($remaining, $remTotal - $len));
            $found = State::whereRaw('LOWER(name) = ?', [strtolower($candidate)])
                          ->where('country_id', $countryObj->id)
                          ->first();
            if ($found) {
                $stateName = $found->name;
                $stateLen  = $len;
                break;
            }
        }

        $cityParts = array_slice($remaining, 0, $remTotal - $stateLen);
        if (!empty($cityParts)) {
            $cityName = implode(' ', $cityParts);
        }

        return [$countryName, $stateName, $cityName];
    }
}
