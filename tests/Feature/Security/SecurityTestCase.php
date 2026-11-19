<?php

namespace Tests\Feature\Security;

use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\SavedLead;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Monolog\Handler\NullHandler;
use Tests\TestCase;

/**
 * Base class for the security regression tests.
 *
 * Runs against the in-memory SQLite database configured in phpunit.xml, built
 * from the real migrations (which are fresh-install compatible since Phase 0.1).
 */
abstract class SecurityTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ExtensionController logs to the "daily" channel — keep test runs from writing log files.
        config(['logging.channels.daily' => ['driver' => 'monolog', 'handler' => NullHandler::class]]);
        config(['logging.default' => 'null']);

        // Never call real third-party APIs from these tests.
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    }

    // ──────────────────────────────────────────────
    // Factories
    // ──────────────────────────────────────────────

    protected function makeUser(array $attributes = []): User
    {
        $first = $attributes['first_name'] ?? 'Test';
        $last = $attributes['last_name'] ?? Str::random(5);

        $user = new User();
        $user->forceFill(array_merge([
            'first_name' => $first,
            'last_name' => $last,
            'name' => "{$first} {$last}",
            'email' => Str::lower(Str::random(10)) . '@example.test',
            'password' => Hash::make('secret-password'),
            'whatsapp_number' => '+923001234567',
            'user_type' => 'user',
            'status' => 'active',
            'login_type' => 'custom',
            'email_verified' => true,
        ], $attributes));
        $user->save();

        return $user->fresh();
    }

    protected function makeAdmin(array $attributes = []): User
    {
        return $this->makeUser(array_merge(['user_type' => 'admin'], $attributes));
    }

    /** A user (or company) with an active subscription whose plan has the given features. */
    protected function makeSubscribedUser(array $attributes = [], array $features = []): User
    {
        $user = $this->makeUser($attributes);
        $this->subscribe($user, $features);

        return $user;
    }

    protected function subscribe(User $user, array $features = []): Subscription
    {
        $package = Package::forceCreate([
            'name' => 'Test Plan ' . Str::random(4),
            'slug' => 'test-plan-' . Str::lower(Str::random(8)),
            'package_for' => $user->user_type === 'company' ? 'company' : 'user',
            'billing_type' => 'monthly',
            'price' => 0,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $features = array_merge([
            'daily_leads_limit' => '50',
            'max_devices' => '2',
            'export_leads' => 'unlimited',
            'team_members' => '5',
        ], $features);

        foreach ($features as $key => $value) {
            PackageFeature::forceCreate([
                'package_id' => $package->id,
                'feature_key' => $key,
                'feature_value' => $value,
                'is_unlimited' => $value === 'unlimited',
            ]);
        }

        return Subscription::forceCreate([
            'package_id' => $package->id,
            'user_id' => $user->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);
    }

    protected function makeLead(User $owner, array $attributes = []): SavedLead
    {
        $lead = new SavedLead();
        $lead->forceFill(array_merge([
            'user_id' => $owner->id,
            'place_id' => 'place-' . Str::random(12),
            'name' => 'Lead ' . Str::random(8),
            'address' => '1 Test Street',
            'contact_status' => 'not_contacted',
        ], $attributes));
        $lead->save();

        return $lead;
    }
}
