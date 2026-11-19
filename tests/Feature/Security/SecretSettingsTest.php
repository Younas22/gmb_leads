<?php

namespace Tests\Feature\Security;

use App\Models\Setting;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;

class SecretSettingsTest extends SecurityTestCase
{
    private function rawValue(string $key): ?string
    {
        return DB::table('settings')->where('key', $key)->value('value');
    }

    public function test_secret_settings_are_encrypted_at_rest_and_decrypted_on_read(): void
    {
        Setting::set('resend_api_key', 're_plain_secret_value', Setting::TYPE_SECRET, 'email');

        $raw = $this->rawValue('resend_api_key');
        $this->assertNotSame('re_plain_secret_value', $raw);
        $this->assertStringNotContainsString('re_plain_secret_value', $raw);
        $this->assertTrue(Setting::looksEncrypted($raw));

        $this->assertSame('re_plain_secret_value', Setting::get('resend_api_key'));
        $this->assertSame('re_plain_secret_value', Setting::getByGroup('email')['resend_api_key']);
    }

    public function test_non_secret_settings_are_unchanged(): void
    {
        Setting::set('from_email', 'noreply@example.test', 'email', 'email');

        $this->assertSame('noreply@example.test', $this->rawValue('from_email'));
        $this->assertSame('noreply@example.test', Setting::get('from_email'));
    }

    public function test_legacy_plaintext_secret_is_still_readable(): void
    {
        DB::table('settings')->insert([
            'key' => 'google_places_api_key', 'value' => 'legacy-plain-key', 'type' => 'password',
            'group' => 'general', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame('legacy-plain-key', Setting::get('google_places_api_key'));
    }

    public function test_undecryptable_secret_returns_null_instead_of_ciphertext(): void
    {
        $otherKey = new Encrypter(random_bytes(32), 'AES-256-CBC');
        DB::table('settings')->insert([
            'key' => 'google_client_secret', 'value' => $otherKey->encryptString('secret'), 'type' => 'password',
            'group' => 'oauth', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertNull(Setting::get('google_client_secret'));
    }

    public function test_encrypt_command_is_dry_run_by_default_then_encrypts_and_can_roll_back(): void
    {
        DB::table('settings')->insert([
            ['key' => 'resend_api_key', 'value' => 're_legacy', 'type' => 'password', 'group' => 'email', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'site_name', 'value' => 'CNM', 'type' => 'text', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('settings:encrypt-secrets')
            ->expectsOutputToContain('1 still plaintext')
            ->expectsOutputToContain('Dry run only')
            ->doesntExpectOutputToContain('re_legacy')
            ->assertSuccessful();
        $this->assertSame('re_legacy', $this->rawValue('resend_api_key'));

        $this->artisan('settings:encrypt-secrets', ['--force' => true])->assertSuccessful();
        $this->assertTrue(Setting::looksEncrypted($this->rawValue('resend_api_key')));
        $this->assertSame('CNM', $this->rawValue('site_name'));
        $this->assertSame('re_legacy', Setting::get('resend_api_key'));

        // Idempotent
        $this->artisan('settings:encrypt-secrets', ['--force' => true])
            ->expectsOutputToContain('0 still plaintext')
            ->assertSuccessful();

        // Rollback path for older releases
        $this->artisan('settings:encrypt-secrets', ['--decrypt' => true, '--force' => true])->assertSuccessful();
        $this->assertSame('re_legacy', $this->rawValue('resend_api_key'));
    }

    public function test_admin_settings_form_shows_decrypted_value_and_saves_encrypted(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/admin/settings/email', [
            'resend_api_key' => 're_admin_saved',
            'from_email' => 'noreply@example.test',
            'from_name' => 'CNM',
        ])->assertRedirect();

        $this->assertTrue(Setting::looksEncrypted($this->rawValue('resend_api_key')));

        $this->actingAs($admin)->get('/admin/settings')
            ->assertOk()
            ->assertSee('re_admin_saved', false);
    }
}
