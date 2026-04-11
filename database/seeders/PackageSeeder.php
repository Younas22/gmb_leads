<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PackageFeature::truncate();
        Package::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        /**
         * =========================
         * FREE TRIAL
         * =========================
         */
        $freeTrial = Package::create([
            'name'         => 'Free Trial',
            'slug'         => 'free-trial',
            'package_for'  => 'user',
            'billing_type' => 'monthly',
            'price'        => 0.00,
            'currency'     => 'USD',
            'max_users'    => 1,
            'is_popular'   => false,
            'description'  => 'Get started with limited map scraping. Ideal for exploring the platform.',
            'status'       => 'active',
        ]);

        $this->addFeatures($freeTrial->id, [
            ['feature_key' => 'unlimited_map_scraping',   'feature_value' => 'false'],
            ['feature_key' => 'daily_leads_limit',        'feature_value' => '50'],
            ['feature_key' => 'basic_business_signals',   'feature_value' => 'true'],
            ['feature_key' => 'contact_ready_leads',      'feature_value' => 'true'],
            ['feature_key' => 'email_scraping',           'feature_value' => 'false'],
            ['feature_key' => 'social_media_scraping',    'feature_value' => 'true'],
            ['feature_key' => 'website_extraction',       'feature_value' => 'true'],
            ['feature_key' => 'latest_review_insights',   'feature_value' => 'true'],
            ['feature_key' => 'advanced_review_filters',  'feature_value' => 'true'],
            ['feature_key' => 'export_leads',             'feature_value' => 'unlimited'],
            ['feature_key' => 'max_devices',              'feature_value' => '1'],
            ['feature_key' => 'priority_support',         'feature_value' => 'false'],
        ]);

        /**
         * =========================
         * STARTER — MONTHLY
         * =========================
         */
        $starterMonthly = Package::create([
            'name'         => 'Starter',
            'slug'         => 'starter-monthly',
            'package_for'  => 'user',
            'billing_type' => 'monthly',
            'price'        => 7.99,
            'currency'     => 'USD',
            'max_users'    => 2,
            'is_popular'   => false,
            'description'  => 'Unlimited map scraping with contact-ready leads. Perfect for outreach.',
            'status'       => 'active',
        ]);

        $this->addFeatures($starterMonthly->id, $this->starterFeatures());

        /**
         * =========================
         * STARTER — YEARLY
         * =========================
         */
        $starterYearly = Package::create([
            'name'         => 'Starter',
            'slug'         => 'starter-yearly',
            'package_for'  => 'user',
            'billing_type' => 'yearly',
            'price'        => 79.90,
            'currency'     => 'USD',
            'max_users'    => 2,
            'is_popular'   => false,
            'description'  => 'Unlimited map scraping with contact-ready leads. Perfect for outreach. Save 2 months with annual billing.',
            'status'       => 'active',
        ]);

        $this->addFeatures($starterYearly->id, $this->starterFeatures());

        /**
         * =========================
         * GROWTH — MONTHLY  ⭐ POPULAR
         * =========================
         */
        $growthMonthly = Package::create([
            'name'         => 'Growth',
            'slug'         => 'growth-monthly',
            'package_for'  => 'user',
            'billing_type' => 'monthly',
            'price'        => 15.99,
            'currency'     => 'USD',
            'max_users'    => 5,
            'is_popular'   => true,
            'description'  => 'Full lead intelligence with email, social & website data. Built for scale.',
            'status'       => 'active',
        ]);

        $this->addFeatures($growthMonthly->id, $this->growthFeatures());

        /**
         * =========================
         * GROWTH — YEARLY  ⭐ POPULAR
         * =========================
         */
        $growthYearly = Package::create([
            'name'         => 'Growth',
            'slug'         => 'growth-yearly',
            'package_for'  => 'user',
            'billing_type' => 'yearly',
            'price'        => 159.90,
            'currency'     => 'USD',
            'max_users'    => 5,
            'is_popular'   => true,
            'description'  => 'Full lead intelligence with email, social & website data. Built for scale. Save 2 months with annual billing.',
            'status'       => 'active',
        ]);

        $this->addFeatures($growthYearly->id, $this->growthFeatures());
    }

    private function starterFeatures(): array
    {
        return [
            ['feature_key' => 'unlimited_map_scraping',   'feature_value' => 'true'],
            ['feature_key' => 'daily_leads_limit',        'feature_value' => 'unlimited'],
            ['feature_key' => 'basic_business_signals',   'feature_value' => 'true'],
            ['feature_key' => 'contact_ready_leads',      'feature_value' => 'true'],
            ['feature_key' => 'email_scraping',           'feature_value' => 'false'],
            ['feature_key' => 'social_media_scraping',    'feature_value' => 'true'],
            ['feature_key' => 'website_extraction',       'feature_value' => 'true'],
            ['feature_key' => 'latest_review_insights',   'feature_value' => 'true'],
            ['feature_key' => 'advanced_review_filters',  'feature_value' => 'true'],
            ['feature_key' => 'export_leads',             'feature_value' => 'unlimited'],
            ['feature_key' => 'max_devices',              'feature_value' => '2'],
            ['feature_key' => 'priority_support',         'feature_value' => 'true'],
        ];
    }

    private function growthFeatures(): array
    {
        return [
            ['feature_key' => 'unlimited_map_scraping',   'feature_value' => 'true'],
            ['feature_key' => 'daily_leads_limit',        'feature_value' => 'unlimited'],
            ['feature_key' => 'basic_business_signals',   'feature_value' => 'true'],
            ['feature_key' => 'contact_ready_leads',      'feature_value' => 'true'],
            ['feature_key' => 'email_scraping',           'feature_value' => 'true'],
            ['feature_key' => 'social_media_scraping',    'feature_value' => 'true'],
            ['feature_key' => 'website_extraction',       'feature_value' => 'true'],
            ['feature_key' => 'latest_review_insights',   'feature_value' => 'true'],
            ['feature_key' => 'advanced_review_filters',  'feature_value' => 'true'],
            ['feature_key' => 'export_leads',             'feature_value' => 'unlimited'],
            ['feature_key' => 'max_devices',              'feature_value' => '5'],
            ['feature_key' => 'priority_support',         'feature_value' => 'true'],
        ];
    }

    private function addFeatures(int $packageId, array $features): void
    {
        foreach ($features as $feature) {
            PackageFeature::create([
                'package_id'    => $packageId,
                'feature_key'   => $feature['feature_key'],
                'feature_value' => $feature['feature_value'],
                'is_unlimited'  => false,
            ]);
        }
    }
}
