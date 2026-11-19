<?php

namespace Tests\Feature\PlanFeatures;

use App\Models\PackageFeature;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserFeatureOverride;
use Tests\Feature\Security\SecurityTestCase;

/**
 * A feature the admin locked (package or per-user) shows as Locked, with the admin's message,
 * on the sidebar, dashboard and subscription page alike, and never links to the locked page.
 */
class LockedFeaturesOnPagesTest extends SecurityTestCase
{
    private function lockInPackage(User $user, string $key, string $message, string $value = '1'): void
    {
        $sub = Subscription::where('user_id', $user->id)->firstOrFail();
        PackageFeature::updateOrCreate(
            ['package_id' => $sub->package_id, 'feature_key' => $key],
            ['feature_value' => $value, 'is_locked' => true, 'lock_message' => $message]
        );
    }

    public function test_dashboard_shows_locked_ai_instead_of_hiding_it(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => '1']);
        $this->lockInPackage($user, 'ai_sales_profile_enabled', 'AI unlocks next month');

        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertSee('data-testid="dashboard-ai-locked"', false)
            ->assertDontSee('data-testid="dashboard-ai"', false)
            ->assertSee('AI unlocks next month')
            ->assertDontSee(route('user.ai-profile.index'), false)
            ->assertDontSee(route('user.ai-outreach.index'), false);
    }

    public function test_locked_ai_quota_and_gmail_show_locked_inside_the_ai_card(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => '1', 'ai_offers_monthly' => '10', 'gmail_sending_enabled' => '1']);
        $this->lockInPackage($user, 'ai_offers_monthly', 'Offers are paused', '10');
        UserFeatureOverride::create(['user_id' => $user->id, 'feature_key' => 'gmail_sending_enabled', 'is_locked' => true, 'lock_message' => 'Gmail paused for you']);

        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertSee('data-testid="dashboard-ai"', false)
            ->assertSee('Offers are paused')
            ->assertSee('Gmail paused for you')
            ->assertSee('data-testid="locked-tile"', false)
            ->assertDontSee(route('user.ai-profile.index') . '#offers', false)
            ->assertDontSee(route('user.email-sending.index'), false);
    }

    public function test_dashboard_and_subscription_plan_boxes_show_locked_exports(): void
    {
        $user = $this->makeSubscribedUser([], ['export_leads' => '50']);
        UserFeatureOverride::create(['user_id' => $user->id, 'feature_key' => 'export_leads', 'is_locked' => true, 'lock_message' => 'Exports locked by admin']);

        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertSee('Exports locked by admin')->assertSee('data-testid="locked-feature"', false);
        $this->actingAs($user)->get('/user/subscription')->assertOk()
            ->assertSee('Exports locked by admin')->assertSee('data-testid="locked-feature"', false);
    }

    public function test_subscription_usage_and_feature_chips_follow_locks(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => '1', 'ai_audits_monthly' => '10', 'priority_support' => 'true', 'email_analytics_enabled' => '1']);
        $this->lockInPackage($user, 'ai_sales_profile_enabled', 'AI is coming soon');
        UserFeatureOverride::create(['user_id' => $user->id, 'feature_key' => 'priority_support', 'is_locked' => true, 'lock_message' => 'Support upgrade pending']);

        $html = $this->actingAs($user)->get('/user/subscription')->assertOk()
            ->assertSee('AI is coming soon')
            ->assertSee('Support upgrade pending')
            ->getContent();

        // Priority Support box shows Locked, not "Included"
        $box = substr($html, strpos($html, '>Priority Support<'), 1200);
        $this->assertStringContainsString('Locked', $box);
        $this->assertStringNotContainsString('Included', $box);
        // Locked AI shows as a locked chip; a feature that is on still shows as included
        $this->assertMatchesRegularExpression('/title="AI is coming soon" data-testid="locked-feature"/', $html);
        $this->assertStringContainsString('Email Analytics', $html);
    }
}
