<?php

namespace Tests\Feature\Security;

class RateLimitingTest extends SecurityTestCase
{
    public function test_web_login_is_throttled_per_email_and_ip(): void
    {
        $user = $this->makeSubscribedUser();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/auth/login', ['email' => $user->email, 'password' => 'wrong-password'])
                ->assertStatus(401);
        }

        $this->postJson('/auth/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(429)
            ->assertJsonStructure(['success', 'message', 'error'])
            ->assertHeader('Retry-After');

        // Even the correct password is refused while throttled
        $this->postJson('/auth/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertStatus(429);
    }

    public function test_extension_login_is_throttled_with_extension_error_shape(): void
    {
        $user = $this->makeSubscribedUser();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/extension/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(401);
        }

        $this->postJson('/api/extension/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(429)
            ->assertJsonStructure(['error', 'message']);
    }

    public function test_login_limit_is_per_account_so_other_users_are_not_blocked(): void
    {
        $victim = $this->makeSubscribedUser();
        $other = $this->makeSubscribedUser();

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/extension/login', ['email' => $victim->email, 'password' => 'wrong']);
        }

        $this->postJson('/api/extension/login', ['email' => $other->email, 'password' => 'secret-password'])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_signup_and_password_reset_requests_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/auth/forgot-password', ['email' => "nobody{$i}@example.test"]);
        }
        $this->postJson('/auth/forgot-password', ['email' => 'nobody@example.test'])->assertStatus(429);

        // Signup shares the same per-IP "auth-sensitive" budget
        $this->postJson('/auth/signup', [
            'first_name' => 'A', 'last_name' => 'B', 'email' => 'new@example.test',
            'password' => 'Password-123', 'password_confirmation' => 'Password-123',
        ])->assertStatus(429);
    }

    public function test_profile_password_change_is_throttled(): void
    {
        $user = $this->makeSubscribedUser();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->put('/user/profile/password', [
                'current_password' => 'guess-' . $i,
                'password' => 'Brand-New-Pass-1',
                'password_confirmation' => 'Brand-New-Pass-1',
            ]);
        }

        $this->actingAs($user)->putJson('/user/profile/password', [
            'current_password' => 'secret-password',
            'password' => 'Brand-New-Pass-1',
            'password_confirmation' => 'Brand-New-Pass-1',
        ])->assertStatus(429);
    }

    public function test_extension_api_allows_normal_scraping_volume(): void
    {
        $user = $this->makeSubscribedUser([], ['daily_leads_limit' => 'unlimited']);
        $token = $this->postJson('/api/extension/login', ['email' => $user->email, 'password' => 'secret-password'])->json('token');
        $auth = ['Authorization' => 'Bearer ' . $token];
        $this->postJson('/api/extension/register-device', ['device_fingerprint' => 'fp-rate'], $auth)->assertCreated();

        // 60 single-lead saves in a row (one per scraped business) must all succeed
        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/extension/save-leads', [
                'device_fingerprint' => 'fp-rate',
                'search_data' => ['query' => 'cafe'],
                'leads' => [['name' => "Cafe {$i}", 'profile' => "place-{$i}"]],
            ], $auth)->assertOk();
        }
    }
}
