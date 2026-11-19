<?php

namespace Tests\Feature\Security;

use App\Models\Package;
use App\Models\PackageFeature;
use App\Support\PlanFeatureCatalog;

/** Pricing (home), subscription page and comparison tables show every plan feature, old and new. */
class PlanFeatureDisplayTest extends SecurityTestCase
{
    private function package(array $features): Package
    {
        $p = Package::forceCreate(['name' => 'Growth', 'slug' => 'growth-' . uniqid(), 'package_for' => 'user', 'billing_type' => 'monthly', 'price' => 19, 'currency' => 'USD', 'status' => 'active']);
        foreach ($features as $k => $v) {
            PackageFeature::forceCreate(['package_id' => $p->id, 'feature_key' => $k, 'feature_value' => $v, 'is_unlimited' => $v === 'unlimited']);
        }

        return $p->load('features');
    }

    public function test_values_are_read_the_same_whatever_way_they_were_stored(): void
    {
        $p = $this->package([
            'email_scraping' => 'true', 'gmail_sending_enabled' => '1', 'priority_support' => 'false', 'resend_campaigns_enabled' => '0',
            'daily_leads_limit' => '50', 'ai_audits_monthly' => 'unlimited', 'export_leads' => '0',
        ]);

        $this->assertSame('true', PlanFeatureCatalog::legacyValue($p, 'email_scraping'));
        $this->assertSame('true', PlanFeatureCatalog::legacyValue($p, 'gmail_sending_enabled'), "'1' counts as on");
        $this->assertNull(PlanFeatureCatalog::legacyValue($p, 'priority_support'));
        $this->assertNull(PlanFeatureCatalog::legacyValue($p, 'resend_campaigns_enabled'));
        $this->assertSame('50', PlanFeatureCatalog::legacyValue($p, 'daily_leads_limit'));
        $this->assertSame('unlimited', PlanFeatureCatalog::legacyValue($p, 'ai_audits_monthly'));
        $this->assertNull(PlanFeatureCatalog::legacyValue($p, 'export_leads'));
        $this->assertSame('true', PlanFeatureCatalog::legacyValue($p, 'ai_sales_profile_enabled'), 'Not listed: app default (on)');
        $this->assertNull(PlanFeatureCatalog::legacyValue($p, 'email_tracking_enabled'), 'Not listed: app default (off)');

        $lines = collect(PlanFeatureCatalog::cardLines($p))->keyBy(0);
        // [label, included, value text, locked]
        $this->assertSame([true, 'Unlimited', false], array_slice($lines['AI Website Analyses'], 1));
        $this->assertSame([true, null, false], array_slice($lines['Send from Gmail'], 1));
        $this->assertSame([false, null, false], array_slice($lines['Email Campaigns'], 1));
    }

    public function test_home_pricing_lists_the_new_features(): void
    {
        $this->package(['daily_leads_limit' => '50', 'ai_email_drafts_monthly' => '100', 'gmail_sending_enabled' => '1', 'resend_campaigns_enabled' => '1']);

        $this->get('/')->assertOk()
            ->assertSee('AI Email Drafts')->assertSee('100/month')->assertSee('Send from Gmail')->assertSee('Email Campaigns')
            ->assertSee('Everything Included by Plan')->assertSee('AI outreach')->assertSee('Resend &amp; campaigns', false);
    }

    public function test_subscription_page_shows_plan_usage_and_active_features(): void
    {
        $user = $this->makeSubscribedUser([], [
            'priority_support' => '1', 'gmail_sending_enabled' => '1', 'email_sends_daily' => '25',
            'ai_audits_monthly' => '10', 'ai_email_drafts_monthly' => 'unlimited', 'resend_campaigns_enabled' => 'true',
        ]);

        $page = $this->actingAs($user)->get('/user/subscription')->assertOk();
        $page->assertSee('data-testid="ai-email-usage"', false)
            ->assertSee('AI Website Analyses')->assertSee('/ 10 this month')
            ->assertSee('Gmail Emails')->assertSee('/ 25 today')
            ->assertSee('Send from Gmail')->assertSee('Email Campaigns')
            ->assertSeeInOrder(['Priority Support', 'Included'])
            ->assertSee('Everything Included by Plan');
    }
}
