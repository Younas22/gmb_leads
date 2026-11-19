<?php

namespace Tests\Feature;

use App\Models\PackageFeature;
use App\Models\Subscription;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Dashboard (reference design): welcome banner with the four steps, real KPI counts (no
 * invented growth badges), AI card or "Coming Soon" banner from the real lock, quick actions,
 * plan card, and the sidebar plan card built from the real subscription.
 */
class DashboardDesignTest extends SecurityTestCase
{
    public function test_banner_kpis_quick_actions_and_plan_render_from_real_data(): void
    {
        $user = $this->makeSubscribedUser(['first_name' => 'Younas']);

        $html = $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertSee('Welcome back, <span class="text-[#F97316]">Younas!</span>', false)
            ->assertSeeInOrder(['1. Add Extension', '2. Find &amp; Save Leads', '3. Analyze with AI', '4. Send &amp; Follow Up'], false)
            ->assertSeeInOrder(['Total Leads', 'Contacted', 'Pending', 'Converted'])
            ->assertSee(route('user.search'), false)
            ->assertSee(route('user.leads'), false)
            ->assertSee(route('user.lead-center.index'), false)
            ->assertSee('View Plan')
            ->assertSee('data-testid="sidebar-plan"', false)
            ->assertSee('Upgrade Plan')
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/\+\d+%/', $html, 'no invented growth badges');
        $this->assertStringNotContainsString('425 of 500 leads used', $html, 'old fake sidebar plan box is gone');
    }

    public function test_locked_ai_shows_the_coming_soon_banner_and_soon_steps(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => '1', 'gmail_sending_enabled' => '1']);
        $sub = Subscription::where('user_id', $user->id)->firstOrFail();
        foreach (['ai_sales_profile_enabled', 'gmail_sending_enabled'] as $key) {
            PackageFeature::updateOrCreate(['package_id' => $sub->package_id, 'feature_key' => $key], ['feature_value' => '1', 'is_locked' => true, 'lock_message' => 'Coming soon']);
        }

        $html = $this->actingAs($user->fresh())->get('/user/dashboard')->assertOk()
            ->assertSee('data-testid="dashboard-ai-locked"', false)
            ->assertSee('Let AI analyze businesses, write personalized emails and help you get replies.')
            ->assertSee('Coming Soon')
            ->assertDontSee(route('user.ai-outreach.index'), false)
            ->getContent();

        $this->assertSame(2, substr_count($html, 'class="dbx-soon"'), 'steps 3 and 4 marked soon');
    }

    public function test_available_ai_keeps_the_full_ai_card(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertSee('data-testid="dashboard-ai"', false)
            ->assertDontSee('data-testid="dashboard-ai-locked"', false)
            ->assertDontSee('class="dbx-soon"', false);
    }
}
