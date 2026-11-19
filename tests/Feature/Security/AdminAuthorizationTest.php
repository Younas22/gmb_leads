<?php

namespace Tests\Feature\Security;

use App\Models\Package;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;

class AdminAuthorizationTest extends SecurityTestCase
{
    /**
     * A representative set of admin endpoints, covering every admin controller and
     * the most dangerous actions (settings, maintenance, payments, subscriptions,
     * exports, bulk email). Routes that need a model id are tested separately.
     */
    public static function adminEndpoints(): array
    {
        return [
            'dashboard'               => ['get', '/admin/dashboard'],
            'users list'              => ['get', '/admin/users'],
            'packages list'           => ['get', '/admin/packages'],
            'package create'          => ['post', '/admin/packages'],
            'subscriptions list'      => ['get', '/admin/subscriptions'],
            'subscription create'     => ['post', '/admin/subscriptions'],
            'payments list'           => ['get', '/admin/payments'],
            'revenue report'          => ['get', '/admin/reports/revenue'],
            'all leads report'        => ['get', '/admin/reports/all-leads'],
            'all leads export'        => ['get', '/admin/reports/export/all-leads'],
            'leads export'            => ['get', '/admin/reports/export/leads'],
            'api usage'               => ['get', '/admin/api-usage'],
            'feedback'                => ['get', '/admin/feedback/history'],
            'affiliate'               => ['get', '/admin/affiliate'],
            'settings page'           => ['get', '/admin/settings'],
            'email settings'          => ['put', '/admin/settings/email'],
            'general settings'        => ['put', '/admin/settings/general'],
            'oauth settings'          => ['put', '/admin/settings/oauth'],
            'system settings'         => ['put', '/admin/settings/system'],
            'bulk feature email'      => ['post', '/admin/settings/email/bulk-new-feature'],
            'bulk maintenance email'  => ['post', '/admin/settings/email/bulk-maintenance'],
            'cache clear'             => ['post', '/admin/settings/cache/clear'],
            'run migrations'          => ['post', '/admin/settings/migrations/run'],
            'run specific migration'  => ['post', '/admin/settings/migrations/run-specific'],
            'run composer'            => ['post', '/admin/settings/composer/run'],
            'switch to user view'     => ['get', '/admin/switch-to-user'],
        ];
    }

    #[DataProvider('adminEndpoints')]
    public function test_regular_user_gets_403_on_admin_endpoints(string $method, string $uri): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->{$method}($uri)->assertForbidden();
        $this->actingAs($user)->{$method . 'Json'}($uri)->assertForbidden();
    }

    #[DataProvider('adminEndpoints')]
    public function test_company_account_gets_403_on_admin_endpoints(string $method, string $uri): void
    {
        $company = $this->makeSubscribedUser(['user_type' => 'company']);

        $this->actingAs($company)->{$method}($uri)->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_admin_endpoints(): void
    {
        $this->get('/admin/settings')->assertRedirect();
        $this->get('/admin/payments')->assertRedirect();
    }

    public function test_regular_user_cannot_change_system_settings(): void
    {
        $user = $this->makeSubscribedUser();
        Setting::set('from_email', 'original@example.test', 'email', 'email');

        $this->actingAs($user)->put('/admin/settings/email', [
            'resend_api_key' => 're_attacker',
            'from_email' => 'attacker@example.test',
            'from_name' => 'Attacker',
        ])->assertForbidden();

        $this->assertSame('original@example.test', Setting::get('from_email'));
        $this->assertNull(Setting::get('resend_api_key'));
    }

    public function test_regular_user_cannot_toggle_packages_or_grant_subscriptions(): void
    {
        $user = $this->makeSubscribedUser();
        $package = Package::first();
        $originalStatus = $package->status;

        $this->actingAs($user)->post("/admin/packages/{$package->id}/toggle-status")->assertForbidden();
        $this->assertSame($originalStatus, $package->fresh()->status);

        $before = Subscription::count();
        $this->actingAs($user)->post('/admin/subscriptions', [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ])->assertForbidden();
        $this->assertSame($before, Subscription::count());
    }

    public function test_admin_can_access_admin_pages(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/admin/packages')->assertOk();
        $this->actingAs($admin)->get('/admin/feedback/history')->assertOk();
        $this->actingAs($admin)->get('/admin/settings/email/verified-users-count')->assertOk();
    }

    public function test_admin_can_save_settings_but_env_file_is_not_rewritten(): void
    {
        $admin = $this->makeAdmin();
        $envPath = base_path('.env');
        $hashBefore = is_file($envPath) ? md5_file($envPath) : null;

        $this->actingAs($admin)->put('/admin/settings/email', [
            'resend_api_key' => 're_test_key',
            'from_email' => 'noreply@example.test',
            'from_name' => 'Example',
        ])->assertRedirect();

        $this->assertSame('noreply@example.test', Setting::get('from_email'));
        $this->assertSame($hashBefore, is_file($envPath) ? md5_file($envPath) : null, '.env must not be modified from the web panel');
    }

    public function test_web_maintenance_commands_are_disabled_even_for_admin(): void
    {
        $admin = $this->makeAdmin();
        Artisan::spy();

        $this->actingAs($admin)->postJson('/admin/settings/migrations/run')->assertForbidden();
        $this->actingAs($admin)->postJson('/admin/settings/migrations/run-specific', [
            'migration' => '0001_01_01_000000_create_users_table',
        ])->assertForbidden();
        $this->actingAs($admin)->postJson('/admin/settings/composer/run', ['command' => 'update'])->assertForbidden();

        Artisan::shouldNotHaveReceived('call', ['migrate', \Mockery::any()]);
    }
}
