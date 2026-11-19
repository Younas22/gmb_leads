<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordExposureTest extends SecurityTestCase
{
    private function plainPasswordOf(User $user): ?string
    {
        return DB::table('users')->where('id', $user->id)->value('plain_password');
    }

    public function test_signup_does_not_store_readable_password(): void
    {
        $this->postJson('/auth/signup', [
            'first_name' => 'New',
            'last_name' => 'Person',
            'email' => 'new.person@example.test',
            'password' => 'Very-Secret-123',
            'password_confirmation' => 'Very-Secret-123',
        ])->assertOk()->assertJsonPath('success', true);

        $user = User::where('email', 'new.person@example.test')->firstOrFail();
        $this->assertNull($this->plainPasswordOf($user));
        $this->assertTrue(Hash::check('Very-Secret-123', $user->password));
    }

    public function test_profile_password_change_does_not_store_readable_password(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->put('/user/profile/password', [
            'current_password' => 'secret-password',
            'password' => 'Brand-New-Pass-1',
            'password_confirmation' => 'Brand-New-Pass-1',
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('Brand-New-Pass-1', $user->password));
        $this->assertNull($this->plainPasswordOf($user));
    }

    public function test_team_member_create_and_show_never_expose_password(): void
    {
        $company = $this->makeSubscribedUser(['user_type' => 'company', 'signups_enabled' => true]);

        $create = $this->actingAs($company)->postJson('/user/team-members', [
            'first_name' => 'Team',
            'last_name' => 'Mate',
            'email' => 'team.mate@example.test',
            'password' => 'Member-Pass-99',
        ]);
        $create->assertOk();
        $this->assertStringNotContainsString('Member-Pass-99', $create->getContent());

        $member = User::where('email', 'team.mate@example.test')->firstOrFail();
        $this->assertNull($this->plainPasswordOf($member));
        $this->assertTrue(Hash::check('Member-Pass-99', $member->password));

        $show = $this->actingAs($company)->getJson("/user/team-members/{$member->id}");
        $show->assertOk()->assertJsonMissingPath('team_member.password');
        $this->assertStringNotContainsString('Member-Pass-99', $show->getContent());

        // Updating with a new password also must not store a readable copy
        $this->actingAs($company)->putJson("/user/team-members/{$member->id}", [
            'first_name' => 'Team',
            'last_name' => 'Mate',
            'email' => 'team.mate@example.test',
            'password' => 'Changed-Pass-77',
        ])->assertOk();
        $this->assertNull($this->plainPasswordOf($member));

        // Team members page no longer renders passwords
        $this->actingAs($company)->get('/user/team-members')
            ->assertOk()
            ->assertDontSee('Member-Pass-99')
            ->assertDontSee('Changed-Pass-77');
    }

    public function test_legacy_plain_password_is_never_returned_to_anyone(): void
    {
        // Simulate an existing account that still has a readable password from before the fix
        $company = $this->makeSubscribedUser(['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);
        DB::table('users')->where('id', $member->id)->update(['plain_password' => 'Legacy-Leaked-Pass']);

        $admin = $this->makeAdmin();

        $adminShow = $this->actingAs($admin)->getJson("/admin/users/{$member->id}");
        $adminShow->assertOk()->assertJsonMissingPath('plain_password');
        $this->assertStringNotContainsString('Legacy-Leaked-Pass', $adminShow->getContent());

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertDontSee('Legacy-Leaked-Pass');

        $this->actingAs($company)->getJson("/user/team-members/{$member->id}")
            ->assertOk()
            ->assertDontSee('Legacy-Leaked-Pass');
        $this->actingAs($company)->get('/user/team-members')->assertOk()->assertDontSee('Legacy-Leaked-Pass');

        // Model serialization hides sensitive columns
        $array = User::find($member->id)->toArray();
        $this->assertArrayNotHasKey('plain_password', $array);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('extension_token', $array);
    }

    public function test_plain_password_is_not_mass_assignable(): void
    {
        $user = $this->makeUser();
        $user->update(['plain_password' => 'should-not-save']);

        $this->assertNull($this->plainPasswordOf($user));
    }

    public function test_purge_command_is_dry_run_by_default_and_clears_with_force(): void
    {
        $user = $this->makeUser();
        DB::table('users')->where('id', $user->id)->update(['plain_password' => 'Legacy-Leaked-Pass']);
        $hashBefore = $user->fresh()->password;

        $this->artisan('security:purge-plain-passwords')
            ->expectsOutputToContain('Users with a readable password stored: 1')
            ->expectsOutputToContain('Dry run only')
            ->assertSuccessful();
        $this->assertSame('Legacy-Leaked-Pass', $this->plainPasswordOf($user));

        $this->artisan('security:purge-plain-passwords', ['--force' => true])
            ->expectsOutputToContain('Cleared plain_password for 1 user(s)')
            ->assertSuccessful();

        $this->assertNull($this->plainPasswordOf($user));
        $this->assertSame($hashBefore, $user->fresh()->password, 'hashed password must be untouched');
    }
}
