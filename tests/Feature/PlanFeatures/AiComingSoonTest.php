<?php

namespace Tests\Feature\PlanFeatures;

use App\Models\Package;
use App\Models\PackageFeature;
use App\Services\PlanFeatures;
use App\Support\PlanFeatureCatalog;
use Tests\Feature\Security\SecurityTestCase;

/**
 * "plan:lock-ai" locks every AI feature in every package with a message, and the public pages
 * then present AI as "coming soon". Unlocking restores the plans exactly.
 */
class AiComingSoonTest extends SecurityTestCase
{
    public function test_command_locks_every_ai_feature_in_every_package_and_unlock_restores_it(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => '1', 'ai_audits_monthly' => '25']);
        $packageCount = Package::count();

        $this->artisan('plan:lock-ai', ['--message' => 'Coming soon'])->assertSuccessful();

        $this->assertSame($packageCount * 4, PackageFeature::whereIn('feature_key', PlanFeatureCatalog::AI_KEYS)->where('is_locked', true)->count());
        $this->assertSame(0, PackageFeature::whereIn('feature_key', PlanFeatureCatalog::AI_KEYS)->where('is_locked', false)->count());
        $this->assertFalse(PlanFeatures::enabled($user->fresh(), 'ai_sales_profile_enabled'));
        $this->assertSame('Coming soon', PlanFeatures::lockMessage($user->fresh(), 'ai_sales_profile_enabled'));
        // the plan's own value is kept
        $this->assertSame('25', PackageFeature::where('feature_key', 'ai_audits_monthly')->where('package_id', $user->activeSubscription()->package_id)->value('feature_value'));

        $this->artisan('plan:lock-ai', ['--unlock' => true])->assertSuccessful();
        $this->assertSame(0, PackageFeature::whereIn('feature_key', PlanFeatureCatalog::AI_KEYS)->where('is_locked', true)->count());
        $this->assertTrue(PlanFeatures::enabled($user->fresh(), 'ai_sales_profile_enabled'));
    }

    public function test_coming_soon_locks_ai_email_and_resend_in_every_package(): void
    {
        $user = $this->makeSubscribedUser([], ['gmail_sending_enabled' => '1', 'email_followups_enabled' => '1', 'resend_campaigns_enabled' => '1']);
        $keys = array_merge(PlanFeatureCatalog::keysOfGroup('ai'), PlanFeatureCatalog::keysOfGroup('email'), PlanFeatureCatalog::keysOfGroup('resend'));
        $this->assertCount(15, $keys);

        $this->artisan('plan:coming-soon')->assertSuccessful();

        $this->assertSame(Package::count() * 15, PackageFeature::whereIn('feature_key', $keys)->where('is_locked', true)->count());
        $this->assertSame(0, PackageFeature::whereIn('feature_key', $keys)->where('is_locked', false)->count());
        foreach (['gmail_sending_enabled', 'email_analytics_enabled', 'email_followups_enabled', 'resend_connection_enabled', 'resend_campaigns_enabled'] as $key) {
            $this->assertFalse(PlanFeatures::enabled($user->fresh(), $key), $key);
            $this->assertSame('Coming soon', PlanFeatures::lockMessage($user->fresh(), $key), $key);
        }
        // lead features stay open
        $this->assertSame(0, PackageFeature::whereIn('feature_key', PlanFeatureCatalog::keysOfGroup('leads'))->where('is_locked', true)->count());

        // the user cannot open the locked pages
        $this->actingAs($user->fresh())->get('/user/email-follow-ups')->assertRedirect(route('user.subscription'));
        $this->actingAs($user->fresh())->get('/user/campaigns')->assertRedirect(route('user.subscription'));

        // homepage: email cards say "Coming soon", pricing shows it too
        $html = $this->get('/')->assertOk()->getContent();
        // AI Analysis, AI Email Drafts, Send & Track and Smart Follow-ups cards carry the tag
        $this->assertSame(4, substr_count($html, 'absolute top-3 right-3 px-2 py-0.5 rounded-full bg-[#111827] text-white text-[10px] font-semibold uppercase tracking-wide">Coming soon</span>'));

        $this->artisan('plan:coming-soon', ['--unlock' => true])->assertSuccessful();
        $this->assertTrue(PlanFeatures::enabled($user->fresh(), 'gmail_sending_enabled'));
    }

    public function test_group_option_limits_the_lock_and_rejects_unknown_groups(): void
    {
        $this->makeSubscribedUser([], ['gmail_sending_enabled' => '1']);

        $this->artisan('plan:coming-soon', ['--group' => ['email']])->assertSuccessful();
        $this->assertSame(0, PackageFeature::whereIn('feature_key', PlanFeatureCatalog::AI_KEYS)->where('is_locked', true)->count());
        $this->assertTrue(PackageFeature::where('feature_key', 'gmail_sending_enabled')->value('is_locked'));

        $this->artisan('plan:coming-soon', ['--group' => ['leads']])->assertFailed();
    }

    public function test_dry_run_saves_nothing(): void
    {
        $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => '1']);
        $this->artisan('plan:lock-ai', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, PackageFeature::whereIn('feature_key', PlanFeatureCatalog::AI_KEYS)->where('is_locked', true)->count());
    }

    public function test_public_pages_show_ai_as_coming_soon_once_locked(): void
    {
        $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => '1', 'ai_email_drafts_monthly' => '100']);

        // before: normal AI copy
        $this->get('/')->assertOk()->assertSee('AI now writes your outreach emails')->assertDontSee('Coming soon: AI');

        $this->artisan('plan:lock-ai')->assertSuccessful();

        $this->get('/')->assertOk()
            ->assertSee('Coming soon: AI that writes your outreach emails')
            ->assertSee('Our AI outreach assistant is almost ready')
            ->assertSee('>Soon<', false)
            ->assertSee('Coming soon')
            ->assertDontSee('AI now writes your outreach emails');

        $this->get('/auth')->assertOk()->assertSee('Outreach<span', false)->assertSee('>Soon<', false);
    }

    public function test_dashboard_shows_the_lock_message_after_locking(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => '1']);
        $this->artisan('plan:lock-ai', ['--message' => 'AI is coming soon'])->assertSuccessful();

        $this->actingAs($user->fresh())->get('/user/dashboard')->assertOk()
            ->assertSee('data-testid="dashboard-ai-locked"', false)
            ->assertSee('AI is coming soon')
            ->assertDontSee(route('user.ai-outreach.index'), false);
    }
}
