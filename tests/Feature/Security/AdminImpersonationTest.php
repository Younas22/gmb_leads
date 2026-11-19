<?php

namespace Tests\Feature\Security;

/**
 * Admin "Login as user": works without any readable password, only admins can start it,
 * admins can't be impersonated, and the admin can switch back.
 */
class AdminImpersonationTest extends SecurityTestCase
{
    public function test_admin_can_log_in_as_a_user_and_switch_back(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeSubscribedUser();

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $user))
            ->assertRedirect(route('user.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($admin->id, session('impersonator_id'));

        $this->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Back to admin')
            ->assertSee('const FB_PROMPT = null', false); // feedback prompt never blocks the admin

        $this->post(route('impersonate.stop'))->assertRedirect(route('admin.users'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_non_admin_cannot_impersonate(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        $this->actingAs($user)->post(route('admin.users.impersonate', $other))->assertForbidden();
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_accounts_cannot_be_impersonated(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.users.impersonate', $otherAdmin));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_stop_without_an_impersonation_session_does_nothing(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('impersonate.stop'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_page_shows_login_as_button_for_non_admins_only(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();

        $this->actingAs($admin)->get(route('admin.users'))
            ->assertOk()
            ->assertSee(route('admin.users.impersonate', $user), false)
            ->assertDontSee(route('admin.users.impersonate', $admin), false)
            ->assertDontSee('plain_password');
    }
}
