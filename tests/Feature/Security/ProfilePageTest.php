<?php

namespace Tests\Feature\Security;

/**
 * Profile Settings page: renders for each account type and only shows what applies to it.
 */
class ProfilePageTest extends SecurityTestCase
{
    public function test_regular_verified_user_sees_all_tabs_badges_and_danger_zone(): void
    {
        $user = $this->makeSubscribedUser(['first_name' => 'Ayesha', 'last_name' => 'Khan', 'last_login' => now()->subHours(2)]);

        $this->actingAs($user)->get('/user/profile')->assertOk()
            ->assertSee('Regular User')
            ->assertSee('Email Verified')
            ->assertSee('Email Login')
            ->assertDontSee('Google Login')
            ->assertSee('2 hours ago')
            ->assertSee('data-tab="security"', false)
            ->assertSee('data-tab="preferences"', false)
            ->assertSee('name="current_password"', false)
            ->assertSee('name="avatar"', false)
            ->assertSee('Danger Zone')
            ->assertSee(route('user.account.delete'), false)
            ->assertSee(route('user.data.clear'), false);
    }

    public function test_google_unverified_user_sees_google_badge_and_verify_action_but_no_password_form(): void
    {
        $user = $this->makeSubscribedUser(['login_type' => 'google', 'email_verified' => false, 'last_login' => null]);

        $this->actingAs($user)->get('/user/profile')->assertOk()
            ->assertSee('Google Login')
            ->assertSee('Verify Email Now')
            ->assertSee('Not verified')
            ->assertSee('Never')
            ->assertSee('Google Account')
            ->assertDontSee('name="current_password"', false);
    }

    public function test_team_member_sees_personal_info_only(): void
    {
        $company = $this->makeSubscribedUser(['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);

        $this->actingAs($member)->get('/user/profile')->assertOk()
            ->assertSee('Personal Information')
            ->assertDontSee('data-tab="security"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('Danger Zone');
    }

    public function test_password_errors_reopen_the_security_tab(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->from('/user/profile')
            ->put('/user/profile/password', ['current_password' => '', 'password' => 'x'])
            ->assertSessionHasErrors(['current_password', 'password']);

        $html = $this->actingAs($user)->get('/user/profile')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="security-tab" role="tabpanel" class="tab-content space-y-3\.5 "/', $html);
        $this->assertMatchesRegularExpression('/id="personal-tab"[^>]*hidden/', $html);
    }
}
