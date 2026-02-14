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
         * USER PACKAGES (MONTHLY)
         * =========================
         */

        // 1️⃣ Starter
        $starter = Package::create([
            'name'         => 'Starter',
            'slug'         => 'starter',
            'package_for'  => 'user',
            'billing_type' => 'monthly',
            'price'        => 0.00,
            'currency'     => 'USD',
            'max_users'    => 1,
            'is_popular'   => false,
            'description' => 'Basic business discovery. Ideal for market research and niche validation.',
            'status'       => 'active',
        ]);

        $this->addFeatures($starter->id, [
            ['feature_key' => 'search_credits',           'feature_value' => '500'],
            ['feature_key' => 'leads_per_month',        'feature_value' => '100'],
            ['feature_key' => 'saved_lists',             'feature_value' => '3'],
            ['feature_key' => 'export_leads',            'feature_value' => '25'],

            // 🔍 Lead data depth
            ['feature_key' => 'data_depth',              'feature_value' => 'starter'],
            ['feature_key' => 'basic_business_signals',  'feature_value' => 'true'],
            ['feature_key' => 'contact_ready_leads',     'feature_value' => 'false'],
            ['feature_key' => 'email_social_discovery',  'feature_value' => 'false'],
            ['feature_key' => 'latest_review_insights',  'feature_value' => 'false'],
            ['feature_key' => 'advanced_review_filters', 'feature_value' => 'false'],
            ['feature_key' => 'full_review_intelligence','feature_value' => 'false'],
        ]);

        // 2️⃣ Growth (MOST POPULAR)
        $growth = Package::create([
            'name'         => 'Growth',
            'slug'         => 'growth',
            'package_for'  => 'user',
            'billing_type' => 'monthly',
            'price'        => 39.00,
            'currency'     => 'USD',
            'max_users'    => 1,
            'is_popular'   => true,
            'description' => 'Contact-ready leads with smart filters. Built for outreach & sales.',
            'status'       => 'active',
        ]);

        $this->addFeatures($growth->id, [
            ['feature_key' => 'search_credits',           'feature_value' => '2000'],
            ['feature_key' => 'leads_per_month',        'feature_value' => '500'],
            ['feature_key' => 'saved_lists',             'feature_value' => '10'],
            ['feature_key' => 'export_leads',            'feature_value' => '250'],

            // 🔥 Lead intelligence
            ['feature_key' => 'data_depth',              'feature_value' => 'growth'],
            ['feature_key' => 'basic_business_signals',  'feature_value' => 'true'],
            ['feature_key' => 'contact_ready_leads',     'feature_value' => 'true'],
            ['feature_key' => 'email_social_discovery',  'feature_value' => 'true'],
            ['feature_key' => 'latest_review_insights',  'feature_value' => 'true'],
            ['feature_key' => 'advanced_review_filters', 'feature_value' => 'true'],
            ['feature_key' => 'full_review_intelligence','feature_value' => 'false'],
        ]);

        // 3️⃣ Pro
        $pro = Package::create([
            'name'         => 'Pro',
            'slug'         => 'pro',
            'package_for'  => 'user',
            'billing_type' => 'monthly',
            'price'        => 79.00,
            'currency'     => 'USD',
            'max_users'    => 1,
            'is_popular'   => false,
            'description' => 'Deep business intelligence with full review data.',
            'status'       => 'active',
        ]);

        $this->addFeatures($pro->id, [
            ['feature_key' => 'search_credits',           'feature_value' => '5000'],
            ['feature_key' => 'leads_per_month',        'feature_value' => '1000'],
            ['feature_key' => 'saved_lists',             'feature_value' => '25'],
            ['feature_key' => 'export_leads',            'feature_value' => '1000'],

            // 🚀 Full power
            ['feature_key' => 'data_depth',              'feature_value' => 'pro'],
            ['feature_key' => 'basic_business_signals',  'feature_value' => 'true'],
            ['feature_key' => 'contact_ready_leads',     'feature_value' => 'true'],
            ['feature_key' => 'email_social_discovery',  'feature_value' => 'true'],
            ['feature_key' => 'latest_review_insights',  'feature_value' => 'true'],
            ['feature_key' => 'advanced_review_filters', 'feature_value' => 'true'],
            ['feature_key' => 'full_review_intelligence','feature_value' => 'true'],
        ]);
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
