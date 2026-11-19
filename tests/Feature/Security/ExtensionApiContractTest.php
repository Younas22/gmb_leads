<?php

namespace Tests\Feature\Security;

use App\Models\SavedLead;
use App\Models\User;
use App\Models\UserExtensionDevice;

/**
 * Locks down the request/response shapes the published Chrome extension
 * (Customernearme_Extension/auth.js, background.js) depends on, so the
 * security work does not break existing installs.
 */
class ExtensionApiContractTest extends SecurityTestCase
{
    private function loginExtension(User $user): string
    {
        return $this->postJson('/api/extension/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');
    }

    private function auth(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_login_returns_token_and_user_shape(): void
    {
        $user = $this->makeSubscribedUser();

        $this->postJson('/api/extension/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'package_slug', 'package_name', 'credits_used', 'credits_limit', 'device_limit'],
            ])
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.plain_password');

        $this->assertSame(64, strlen($user->fresh()->extension_token));
    }

    public function test_login_reuses_existing_token_for_multiple_devices(): void
    {
        $user = $this->makeSubscribedUser();

        $first = $this->loginExtension($user);
        $second = $this->loginExtension($user);

        $this->assertSame($first, $second);
    }

    public function test_login_error_shapes_are_unchanged(): void
    {
        $user = $this->makeSubscribedUser();
        $this->postJson('/api/extension/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(401)->assertJsonStructure(['error']);

        $this->postJson('/api/extension/login', ['email' => 'not-an-email'])
            ->assertStatus(422)->assertJsonStructure(['error', 'details']);

        $noPlan = $this->makeUser();
        $this->postJson('/api/extension/login', ['email' => $noPlan->email, 'password' => 'secret-password'])
            ->assertStatus(403)->assertJsonStructure(['error']);

        $admin = $this->makeAdmin();
        $this->postJson('/api/extension/login', ['email' => $admin->email, 'password' => 'secret-password'])
            ->assertStatus(403)->assertJsonStructure(['error']);
    }

    public function test_web_auto_login_returns_token_for_session_user(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->getJson('/extension/web-token')
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'package_name', 'credits_used', 'credits_limit', 'device_limit']]);
    }

    public function test_device_registration_status_and_listing_shapes(): void
    {
        $user = $this->makeSubscribedUser();
        $token = $this->loginExtension($user);

        $this->postJson('/api/extension/register-device', [
            'device_fingerprint' => 'fp-123',
            'device_name' => 'Chrome on Windows',
        ], $this->auth($token))
            ->assertCreated()
            ->assertJsonStructure(['message', 'device_id']);

        // Re-registering the same device is idempotent
        $this->postJson('/api/extension/register-device', ['device_fingerprint' => 'fp-123'], $this->auth($token))
            ->assertOk()->assertJsonStructure(['message', 'device_id']);

        $this->getJson('/api/extension/status?device_fingerprint=fp-123', $this->auth($token))
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'subscription' => ['package_name', 'package_slug', 'status', 'expires_at'],
                'credits' => ['used', 'limit', 'remaining', 'has_credits'],
                'devices' => ['limit', 'used'],
            ]);

        $this->getJson('/api/extension/devices', $this->auth($token))
            ->assertOk()
            ->assertJsonStructure(['devices' => [['id', 'device_name', 'device_fingerprint', 'last_seen_at', 'created_at']], 'devices_used', 'device_limit']);
    }

    public function test_save_leads_contract_and_ownership(): void
    {
        $user = $this->makeSubscribedUser();
        $other = $this->makeSubscribedUser();
        $token = $this->loginExtension($user);

        $this->postJson('/api/extension/register-device', ['device_fingerprint' => 'fp-abc'], $this->auth($token))->assertCreated();

        $payload = [
            'device_fingerprint' => 'fp-abc',
            'search_data' => ['query' => 'plumber'],
            'leads' => [[
                'name' => 'Contract Plumbing Co',
                'address' => '42 Pipe Street',
                'phone' => '+1 555 0100',
                'website' => 'https://plumbing.example',
                'emails' => ['hello@plumbing.example'],
                'rating' => 4.6,
                'total_reviews' => 12,
                'profile' => 'https://www.google.com/maps/place/x/data=!19sChIJcontract123',
                'social_links' => ['facebook' => 'https://facebook.com/plumbing'],
                'opening_hours' => [],
                'reviews' => [],
            ]],
        ];

        $this->postJson('/api/extension/save-leads', $payload, $this->auth($token))
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'saved_count', 'duplicate_count', 'limit_skipped', 'daily_limit', 'used_today', 'remaining_today'])
            ->assertJsonPath('saved_count', 1)
            ->assertJsonPath('duplicate_count', 0);

        $lead = SavedLead::firstOrFail();
        $this->assertSame($user->id, $lead->user_id);
        $this->assertSame('hello@plumbing.example', $lead->email);
        $this->assertSame('extension', $lead->found_via_api);

        // Same lead again is reported as a duplicate, not saved twice
        $this->postJson('/api/extension/save-leads', $payload, $this->auth($token))
            ->assertOk()
            ->assertJsonPath('saved_count', 0)
            ->assertJsonPath('duplicate_count', 1);

        $this->assertSame(1, SavedLead::count());
        $this->assertSame(0, SavedLead::where('user_id', $other->id)->count());
    }

    public function test_save_leads_rejects_unknown_token_and_unregistered_device(): void
    {
        $user = $this->makeSubscribedUser();
        $token = $this->loginExtension($user);
        $payload = ['device_fingerprint' => 'never-registered', 'leads' => [['name' => 'X']]];

        $this->postJson('/api/extension/save-leads', $payload, $this->auth('not-a-real-token'))
            ->assertStatus(401)->assertJsonStructure(['error']);
        $this->postJson('/api/extension/save-leads', $payload)
            ->assertStatus(401);

        $this->postJson('/api/extension/save-leads', $payload, $this->auth($token))
            ->assertStatus(403)->assertJsonStructure(['error']);

        $this->assertSame(0, SavedLead::count());
    }

    public function test_device_of_another_user_cannot_be_removed(): void
    {
        $alice = $this->makeSubscribedUser();
        $bob = $this->makeSubscribedUser();
        $bobDevice = UserExtensionDevice::create([
            'user_id' => $bob->id,
            'device_fingerprint' => 'bob-fp',
            'device_name' => 'Bob PC',
            'last_seen_at' => now(),
            'is_active' => true,
        ]);

        $this->deleteJson("/api/extension/devices/{$bobDevice->id}", [], $this->auth($this->loginExtension($alice)))
            ->assertNotFound();

        $this->assertNotNull($bobDevice->fresh());
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->makeSubscribedUser();
        $token = $this->loginExtension($user);

        $this->postJson('/api/extension/logout', [], $this->auth($token))->assertOk()->assertJsonStructure(['message']);
        $this->getJson('/api/extension/status', $this->auth($token))->assertStatus(401);
    }
}
