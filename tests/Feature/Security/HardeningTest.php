<?php

namespace Tests\Feature\Security;

use App\Models\LeadCenterLead;
use App\Models\SavedLead;
use Composer\Semver\Semver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class HardeningTest extends SecurityTestCase
{
    // ── Temporary / debug routes ─────────────────────────────────────────

    public function test_temporary_and_debug_routes_are_removed(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeSubscribedUser();

        $this->actingAs($admin)->get('/temp-affiliate-fix?action=status')->assertNotFound();
        $this->actingAs($user)->get('/user/debug-reviews')->assertNotFound();
    }

    // ── Sensitive request logging ────────────────────────────────────────

    public function test_signup_does_not_log_personal_data_or_passwords(): void
    {
        Log::spy();

        $this->postJson('/auth/signup', [
            'first_name' => 'Log', 'last_name' => 'Check', 'email' => 'log.check@example.test',
            'password' => 'Secret-Pass-123', 'password_confirmation' => 'Secret-Pass-123',
        ])->assertOk();

        Log::shouldHaveReceived('info')->with('Signup API hit', \Mockery::on(
            fn ($context) => is_array($context)
                && !array_key_exists('email', $context)
                && !array_key_exists('password', $context)
                && !str_contains(json_encode($context), 'log.check@example.test')
        ));
    }

    public function test_profile_update_does_not_log_request_payload(): void
    {
        $code = file_get_contents(app_path('Http/Controllers/ProfileController.php'));
        $this->assertStringNotContainsString("Log::info('User update request received', \$request->all())", $code);
    }

    // ── Fresh-install migrations ─────────────────────────────────────────

    public function test_fresh_migrations_create_location_tables(): void
    {
        foreach (['countries', 'states', 'cities'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} should exist after migrate");
        }
    }

    public function test_fresh_migrations_apply_the_extended_status_enums(): void
    {
        $user = $this->makeSubscribedUser();

        // Values that only exist after the ALTER ... ENUM migrations
        $lead = $this->makeLead($user, ['contact_status' => 'converted']);
        $this->assertSame('converted', $lead->fresh()->contact_status);

        $lcLead = LeadCenterLead::forceCreate([
            'user_id' => $user->id, 'company_name' => 'Enum Co', 'dedupe_key' => 'enum-co', 'status' => 'follow_up_2',
        ]);
        $this->assertSame('follow_up_2', $lcLead->fresh()->status);

        LeadCenterLead::whereKey($lcLead->id)->update(['status' => 'converted']);
        $this->assertSame('converted', $lcLead->fresh()->status);
    }

    // ── PHP / Composer compatibility ─────────────────────────────────────

    public function test_composer_lock_is_installable_on_the_declared_minimum_php(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true);

        $platformPhp = $composer['config']['platform']['php'] ?? null;
        $this->assertSame('8.2.0', $platformPhp, 'config.platform.php pins resolution to the minimum supported PHP');
        $this->assertTrue(Semver::satisfies($platformPhp, $composer['require']['php']));

        $incompatible = [];
        foreach (array_merge($lock['packages'], $lock['packages-dev']) as $package) {
            $constraint = $package['require']['php'] ?? null;
            if ($constraint && !Semver::satisfies($platformPhp, $constraint)) {
                $incompatible[] = "{$package['name']} {$package['version']} (php {$constraint})";
            }
        }

        $this->assertSame([], $incompatible, 'Locked packages that need a newer PHP: ' . implode(', ', $incompatible));
    }
}
