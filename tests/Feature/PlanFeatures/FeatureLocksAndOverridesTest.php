<?php

namespace Tests\Feature\PlanFeatures;

use App\Models\PackageFeature;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserFeatureOverride;
use App\Services\FeatureOverrides;
use App\Services\PlanFeatures;
use App\Support\PlanFeatureCatalog;
use Tests\Feature\Security\SecurityTestCase;

class FeatureLocksAndOverridesTest extends SecurityTestCase
{
    private function lockInPackage(User $user, string $key, ?string $message): void
    {
        $sub = Subscription::where('user_id', $user->id)->firstOrFail();
        PackageFeature::updateOrCreate(
            ['package_id' => $sub->package_id, 'feature_key' => $key],
            ['feature_value' => '1', 'is_locked' => true, 'lock_message' => $message]
        );
    }

    public function test_a_locked_package_feature_is_off_and_shown_locked_with_the_message(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => '1', 'email_analytics_enabled' => '1']);
        $this->lockInPackage($user, 'ai_sales_profile_enabled', 'Coming soon for your plan');

        $this->assertFalse(PlanFeatures::enabled($user, 'ai_sales_profile_enabled'));
        $this->assertSame('Coming soon for your plan', PlanFeatures::lockMessage($user, 'ai_sales_profile_enabled'));
        $this->assertTrue(PlanFeatures::enabled($user, 'email_analytics_enabled'));
        $this->assertNull(PlanFeatures::lockMessage($user, 'email_analytics_enabled'));

        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertSee('data-testid="locked-nav-item"', false)
            ->assertSee('Coming soon for your plan')
            ->assertSee('AI Sales Profile')
            ->assertDontSee(route('user.ai-profile.index'), false)   // no link to the locked page
            ->assertSee(route('user.email-analytics.index'), false); // other features unchanged

        // The page itself is closed, with the admin's message
        $this->actingAs($user)->get('/user/ai-sales-profile')
            ->assertRedirect(route('user.subscription'))
            ->assertSessionHas('warning', 'Coming soon for your plan');
        $this->actingAs($user)->getJson('/user/ai-outreach')->assertStatus(403)->assertJsonPath('message', 'Coming soon for your plan');
    }

    public function test_a_lock_without_a_message_uses_the_default_one(): void
    {
        $user = $this->makeSubscribedUser([], ['resend_campaigns_enabled' => '1']);
        $this->lockInPackage($user, 'resend_campaigns_enabled', null);

        $this->assertSame(FeatureOverrides::DEFAULT_LOCK_MESSAGE, PlanFeatures::lockMessage($user, 'resend_campaigns_enabled'));
        $this->actingAs($user)->get('/user/dashboard')->assertOk()->assertSee(FeatureOverrides::DEFAULT_LOCK_MESSAGE);
    }

    public function test_a_feature_that_is_simply_not_in_the_plan_stays_hidden(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => '0']);

        $this->assertNull(PlanFeatures::lockMessage($user, 'ai_sales_profile_enabled'));
        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertDontSee('data-testid="locked-nav-item"', false)->assertDontSee('AI Sales Profile');
    }

    public function test_a_user_override_wins_over_the_package(): void
    {
        $user = $this->makeSubscribedUser([], ['email_analytics_enabled' => '0', 'daily_leads_limit' => '50']);
        UserFeatureOverride::create(['user_id' => $user->id, 'feature_key' => 'email_analytics_enabled', 'feature_value' => 'true']);
        UserFeatureOverride::create(['user_id' => $user->id, 'feature_key' => 'daily_leads_limit', 'feature_value' => '500']);
        UserFeatureOverride::create(['user_id' => $user->id, 'feature_key' => 'export_leads', 'is_unlimited' => true]);

        $this->assertTrue(PlanFeatures::enabled($user, 'email_analytics_enabled'));
        $this->assertSame(500, $user->getFeatureLimit('daily_leads_limit'));
        $this->assertSame(-1, $user->getFeatureLimit('export_leads'));
        $this->actingAs($user)->get('/user/email-analytics')->assertOk();
    }

    public function test_a_user_lock_closes_a_feature_the_package_includes(): void
    {
        $user = $this->makeSubscribedUser([], ['email_followups_enabled' => '1']);
        UserFeatureOverride::create(['user_id' => $user->id, 'feature_key' => 'email_followups_enabled', 'is_locked' => true, 'lock_message' => 'Ask support to unlock']);

        $this->assertFalse(PlanFeatures::enabled($user, 'email_followups_enabled'));
        $this->actingAs($user)->get('/user/dashboard')->assertOk()->assertSee('Ask support to unlock');
        $this->actingAs($user)->get('/user/email-follow-ups')->assertRedirect(route('user.subscription'));
    }

    public function test_team_members_follow_the_company_owners_overrides(): void
    {
        $company = $this->makeSubscribedUser(['user_type' => 'company'], ['email_analytics_enabled' => '1']);
        $member = $this->makeUser(['company_id' => $company->id]);
        UserFeatureOverride::create(['user_id' => $company->id, 'feature_key' => 'email_analytics_enabled', 'is_locked' => true, 'lock_message' => 'Locked for this company']);

        $this->assertFalse(PlanFeatures::enabled($member, 'email_analytics_enabled'));
        $this->assertSame('Locked for this company', PlanFeatures::lockMessage($member, 'email_analytics_enabled'));
    }

    public function test_pricing_treats_a_locked_package_feature_as_not_included(): void
    {
        $user = $this->makeSubscribedUser([], ['priority_support' => 'true']);
        $package = Subscription::where('user_id', $user->id)->firstOrFail()->package;
        $this->assertSame('true', PlanFeatureCatalog::legacyValue($package->fresh('features'), 'priority_support'));

        $this->lockInPackage($user, 'priority_support', 'Soon');
        $this->assertNull(PlanFeatureCatalog::legacyValue($package->fresh('features'), 'priority_support'));
    }

    public function test_admin_saves_a_feature_lock_and_message_on_a_package(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeSubscribedUser();
        $package = Subscription::where('user_id', $user->id)->firstOrFail()->package;

        $this->actingAs($admin)->put(route('admin.packages.update', $package), [
            'name' => $package->name, 'package_for' => 'user', 'billing_type' => 'monthly', 'price' => 10,
            'currency' => 'USD', 'status' => 'active',
            'features' => [
                ['key' => 'daily_leads_limit', 'value' => '50'],
                ['key' => 'resend_campaigns_enabled', 'value' => '1', 'is_locked' => '1', 'lock_message' => 'Coming in December'],
                ['key' => 'email_analytics_enabled', 'value' => '1', 'lock_message' => 'ignored when not locked'],
            ],
        ])->assertRedirect(route('admin.packages.index'));

        $locked = PackageFeature::where('package_id', $package->id)->where('feature_key', 'resend_campaigns_enabled')->firstOrFail();
        $this->assertTrue($locked->is_locked);
        $this->assertSame('Coming in December', $locked->lock_message);
        $open = PackageFeature::where('package_id', $package->id)->where('feature_key', 'email_analytics_enabled')->firstOrFail();
        $this->assertFalse($open->is_locked);
        $this->assertNull($open->lock_message);

        $this->actingAs($admin)->get(route('admin.packages.index'))->assertOk()->assertSee('1 locked');
        $this->actingAs($admin)->getJson(route('admin.packages.edit', $package))->assertOk()
            ->assertJsonFragment(['feature_key' => 'resend_campaigns_enabled', 'is_locked' => true, 'lock_message' => 'Coming in December']);
    }

    public function test_admin_customises_features_for_one_user(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeSubscribedUser([], ['email_analytics_enabled' => '0', 'gmail_sending_enabled' => '1']);
        $other = $this->makeSubscribedUser([], ['email_analytics_enabled' => '0']);
        $sub = Subscription::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.subscriptions.features', $sub))->assertOk()
            ->assertSee('Custom features')->assertSee('Email Analytics')->assertSee('team_members'); // catalog + package-only keys

        $this->actingAs($admin)->put(route('admin.subscriptions.features.update', $sub), ['features' => [
            'email_analytics_enabled' => ['mode' => 'custom', 'value' => 'true'],
            'gmail_sending_enabled' => ['mode' => 'locked', 'lock_message' => 'Verify your domain first'],
            'daily_leads_limit' => ['mode' => 'custom', 'value' => '', 'is_unlimited' => '1'],
            'export_leads' => ['mode' => 'package', 'value' => '999'],
            'not_a_real_key' => ['mode' => 'custom', 'value' => '1'],
        ]])->assertRedirect(route('admin.subscriptions.features', $sub));

        $this->assertSame(3, UserFeatureOverride::where('user_id', $user->id)->count());
        $this->assertTrue(PlanFeatures::enabled($user->fresh(), 'email_analytics_enabled'));
        $this->assertSame('Verify your domain first', PlanFeatures::lockMessage($user->fresh(), 'gmail_sending_enabled'));
        $this->assertSame(-1, $user->fresh()->getFeatureLimit('daily_leads_limit'));
        $this->assertFalse(PlanFeatures::enabled($other, 'email_analytics_enabled')); // nobody else changes

        $this->actingAs($admin)->get(route('admin.subscriptions.index'))->assertOk()->assertSee(route('admin.subscriptions.features', $sub), false);

        // Back to "Use package" for everything
        $this->actingAs($admin)->put(route('admin.subscriptions.features.update', $sub), ['features' => [
            'email_analytics_enabled' => ['mode' => 'package'],
        ]]);
        $this->assertSame(0, UserFeatureOverride::where('user_id', $user->id)->count());
    }

    public function test_only_admins_can_customise_features(): void
    {
        $user = $this->makeSubscribedUser();
        $sub = Subscription::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->get(route('admin.subscriptions.features', $sub))->assertForbidden();
        $this->actingAs($user)->put(route('admin.subscriptions.features.update', $sub), ['features' => [
            'email_analytics_enabled' => ['mode' => 'custom', 'value' => 'true'],
        ]])->assertForbidden();
        $this->assertSame(0, UserFeatureOverride::count());
    }

    public function test_lock_messages_are_escaped(): void
    {
        $user = $this->makeSubscribedUser([], ['email_analytics_enabled' => '1']);
        UserFeatureOverride::create(['user_id' => $user->id, 'feature_key' => 'email_analytics_enabled', 'is_locked' => true, 'lock_message' => '<script>alert(1)</script>']);

        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
