<?php

namespace Tests\Feature\Security;

use App\Support\AdminPasswordCopy;
use Illuminate\Support\Facades\DB;

/**
 * Admin "Show password" on /admin/users: the copy is stored encrypted (never plain text),
 * filled on login, readable only by admins, and never serialized.
 */
class AdminPasswordViewTest extends SecurityTestCase
{
    public function test_login_saves_an_encrypted_copy_that_only_admins_can_reveal(): void
    {
        $user = $this->makeUser();
        $this->postJson(route('auth.login'), ['email' => $user->email, 'password' => 'secret-password'])->assertOk();

        $stored = DB::table('users')->where('id', $user->id)->value('password_copy');
        $this->assertNotNull($stored);
        $this->assertStringNotContainsString('secret-password', $stored);
        $this->assertSame('secret-password', AdminPasswordCopy::reveal($user));
        $this->assertArrayNotHasKey('password_copy', $user->fresh()->toArray());

        $admin = $this->makeAdmin();
        $this->actingAs($admin)->postJson(route('admin.users.password', $user))
            ->assertOk()->assertJsonPath('password', 'secret-password');

        $other = $this->makeUser();
        $this->actingAs($other)->postJson(route('admin.users.password', $user))->assertForbidden();
    }

    public function test_missing_copy_admins_and_page_column(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $otherAdmin = $this->makeAdmin();
        AdminPasswordCopy::store($otherAdmin, 'admin-pass');

        $this->actingAs($admin)->postJson(route('admin.users.password', $user))
            ->assertOk()->assertJsonPath('password', null);
        $this->actingAs($admin)->postJson(route('admin.users.password', $otherAdmin))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.users'))->assertOk()
            ->assertSee(route('admin.users.password', $user), false)
            ->assertDontSee(route('admin.users.password', $otherAdmin), false);
    }

    public function test_password_change_updates_the_copy(): void
    {
        $user = $this->makeSubscribedUser();
        AdminPasswordCopy::store($user, 'secret-password');

        $this->actingAs($user)->put(route('user.password.update'), [
            'current_password' => 'secret-password',
            'password' => 'New-Pass-12345',
            'password_confirmation' => 'New-Pass-12345',
        ]);

        $this->assertSame('New-Pass-12345', AdminPasswordCopy::reveal($user));
    }
}
