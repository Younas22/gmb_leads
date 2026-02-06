<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PackageFeature::truncate();
        Package::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ============ USER PACKAGES (4) ============

        // 1. Free
        $free = Package::create([
            'name'         => 'Free',
            'slug'         => 'free',
            'package_for'  => 'user',
            'billing_type' => 'monthly',
            'price'        => 0.00,
            'currency'     => 'USD',
            'max_users'    => 1,
            'is_popular'   => false,
            'description'  => 'Get started with basic GMB lead generation. Perfect for trying out our platform.',
            'status'       => 'active',
        ]);

        $this->addFeatures($free->id, [
            ['feature_key' => 'gmb_searches',    'feature_value' => '50',  'is_unlimited' => false],
            ['feature_key' => 'leads_per_month',  'feature_value' => '100', 'is_unlimited' => false],
            ['feature_key' => 'export_leads',     'feature_value' => '25',  'is_unlimited' => false],
            ['feature_key' => 'saved_lists',      'feature_value' => '3',   'is_unlimited' => false],
            ['feature_key' => 'api_limit',        'feature_value' => '1',   'is_unlimited' => false],
            ['feature_key' => 'email_support',    'feature_value' => 'false', 'is_unlimited' => false],
            ['feature_key' => 'api_access',       'feature_value' => 'false', 'is_unlimited' => false],
            ['feature_key' => 'bulk_export',      'feature_value' => 'false', 'is_unlimited' => false],
            ['feature_key' => 'crm_integration',  'feature_value' => 'false', 'is_unlimited' => false],
        ]);

        // 2. Pro – Monthly
        $proMonthly = Package::create([
            'name'         => 'Pro',
            'slug'         => 'pro-monthly',
            'package_for'  => 'user',
            'billing_type' => 'monthly',
            'price'        => 29.00,
            'currency'     => 'USD',
            'max_users'    => 1,
            'is_popular'   => true,
            'description'  => 'Ideal for freelancers and small agencies. Unlock bulk export and higher limits.',
            'status'       => 'active',
        ]);

        $this->addFeatures($proMonthly->id, [
            ['feature_key' => 'gmb_searches',    'feature_value' => '500',  'is_unlimited' => false],
            ['feature_key' => 'leads_per_month',  'feature_value' => '1000', 'is_unlimited' => false],
            ['feature_key' => 'export_leads',     'feature_value' => '500',  'is_unlimited' => false],
            ['feature_key' => 'saved_lists',      'feature_value' => '10',   'is_unlimited' => false],
            ['feature_key' => 'api_limit',        'feature_value' => '3',   'is_unlimited' => false],
            ['feature_key' => 'email_support',    'feature_value' => 'true', 'is_unlimited' => false],
            ['feature_key' => 'api_access',       'feature_value' => 'false', 'is_unlimited' => false],
            ['feature_key' => 'bulk_export',      'feature_value' => 'true', 'is_unlimited' => false],
            ['feature_key' => 'crm_integration',  'feature_value' => 'false', 'is_unlimited' => false],
        ]);

        // 3. Pro – Yearly
        $proYearly = Package::create([
            'name'         => 'Pro',
            'slug'         => 'pro-yearly',
            'package_for'  => 'user',
            'billing_type' => 'yearly',
            'price'        => 290.00,
            'currency'     => 'USD',
            'max_users'    => 1,
            'is_popular'   => true,
            'description'  => 'Ideal for freelancers and small agencies. Save 2 months with yearly billing!',
            'status'       => 'active',
        ]);

        $this->addFeatures($proYearly->id, [
            ['feature_key' => 'gmb_searches',    'feature_value' => '500',  'is_unlimited' => false],
            ['feature_key' => 'leads_per_month',  'feature_value' => '1000', 'is_unlimited' => false],
            ['feature_key' => 'export_leads',     'feature_value' => '500',  'is_unlimited' => false],
            ['feature_key' => 'saved_lists',      'feature_value' => '10',   'is_unlimited' => false],
            ['feature_key' => 'api_limit',        'feature_value' => '3',   'is_unlimited' => false],
            ['feature_key' => 'email_support',    'feature_value' => 'true', 'is_unlimited' => false],
            ['feature_key' => 'api_access',       'feature_value' => 'false', 'is_unlimited' => false],
            ['feature_key' => 'bulk_export',      'feature_value' => 'true', 'is_unlimited' => false],
            ['feature_key' => 'crm_integration',  'feature_value' => 'false', 'is_unlimited' => false],
        ]);

        // 4. Pro – Lifetime
        $proLifetime = Package::create([
            'name'         => 'Pro',
            'slug'         => 'pro-lifetime',
            'package_for'  => 'user',
            'billing_type' => 'lifetime',
            'price'        => 499.00,
            'currency'     => 'USD',
            'max_users'    => 1,
            'is_popular'   => false,
            'description'  => 'Pay once, use forever. Get Pro-level access with no recurring fees. Limited time offer!',
            'status'       => 'active',
        ]);

        $this->addFeatures($proLifetime->id, [
            ['feature_key' => 'gmb_searches',    'feature_value' => '2500', 'is_unlimited' => false],
            ['feature_key' => 'leads_per_month',  'feature_value' => '5000', 'is_unlimited' => false],
            ['feature_key' => 'export_leads',     'feature_value' => '2500', 'is_unlimited' => false],
            ['feature_key' => 'saved_lists',      'feature_value' => '50',   'is_unlimited' => false],
            ['feature_key' => 'email_support',    'feature_value' => 'true', 'is_unlimited' => false],
            ['feature_key' => 'api_access',       'feature_value' => 'true', 'is_unlimited' => false],
            ['feature_key' => 'bulk_export',      'feature_value' => 'true', 'is_unlimited' => false],
            ['feature_key' => 'crm_integration',  'feature_value' => 'true', 'is_unlimited' => false],
            ['feature_key' => 'api_calls',        'feature_value' => '10000', 'is_unlimited' => false],
        ]);

        // ============ COMPANY PACKAGES (3) ============

        // 5. Free
        $free = Package::create([
            'name'         => 'Free',
            'slug'         => 'business-free',
            'package_for'  => 'company',
            'billing_type' => 'monthly',
            'price'        => 0.00,
            'currency'     => 'USD',
            'max_users'    => 2,
            'is_popular'   => false,
            'description'  => 'Get started with basic GMB lead generation. Perfect for trying out our platform.',
            'status'       => 'active',
        ]);

        $this->addFeatures($free->id, [
            ['feature_key' => 'gmb_searches',    'feature_value' => '50',  'is_unlimited' => false],
            ['feature_key' => 'leads_per_month',  'feature_value' => '100', 'is_unlimited' => false],
            ['feature_key' => 'export_leads',     'feature_value' => '25',  'is_unlimited' => false],
            ['feature_key' => 'saved_lists',      'feature_value' => '3',   'is_unlimited' => false],
            ['feature_key' => 'email_support',    'feature_value' => 'false', 'is_unlimited' => false],
            ['feature_key' => 'api_access',       'feature_value' => 'false', 'is_unlimited' => false],
            ['feature_key' => 'bulk_export',      'feature_value' => 'false', 'is_unlimited' => false],
            ['feature_key' => 'crm_integration',  'feature_value' => 'false', 'is_unlimited' => false],
        ]);

        // 6. Business – Monthly
        $bizMonthly = Package::create([
            'name'         => 'Business',
            'slug'         => 'business-monthly',
            'package_for'  => 'company',
            'billing_type' => 'monthly',
            'price'        => 299.00,
            'currency'     => 'USD',
            'max_users'    => 5,
            'is_popular'   => false,
            'description'  => 'Perfect for small teams. Collaborate with up to 5 team members on lead generation projects.',
            'status'       => 'active',
        ]);

        $this->addFeatures($bizMonthly->id, [
            ['feature_key' => 'gmb_searches',      'feature_value' => '10000',  'is_unlimited' => false],
            ['feature_key' => 'leads_per_month',    'feature_value' => '25000',  'is_unlimited' => false],
            ['feature_key' => 'export_leads',       'feature_value' => '10000',  'is_unlimited' => false],
            ['feature_key' => 'saved_lists',        'feature_value' => '100',    'is_unlimited' => false],
            ['feature_key' => 'email_support',      'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'api_access',         'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'bulk_export',        'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'crm_integration',    'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'priority_support',   'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'api_calls',          'feature_value' => '100000', 'is_unlimited' => false],
            ['feature_key' => 'team_members',       'feature_value' => '5',      'is_unlimited' => false],
            ['feature_key' => 'team_analytics',     'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'dedicated_manager',  'feature_value' => 'false',  'is_unlimited' => false],
            ['feature_key' => 'white_label',        'feature_value' => 'false',  'is_unlimited' => false],
        ]);

        // 7. Business – Yearly
        $bizYearly = Package::create([
            'name'         => 'Business',
            'slug'         => 'business-yearly',
            'package_for'  => 'company',
            'billing_type' => 'yearly',
            'price'        => 2990.00,
            'currency'     => 'USD',
            'max_users'    => 5,
            'is_popular'   => true,
            'description'  => 'Perfect for small teams. Save 2 months with yearly billing!',
            'status'       => 'active',
        ]);

        $this->addFeatures($bizYearly->id, [
            ['feature_key' => 'gmb_searches',      'feature_value' => '10000',  'is_unlimited' => false],
            ['feature_key' => 'leads_per_month',    'feature_value' => '25000',  'is_unlimited' => false],
            ['feature_key' => 'export_leads',       'feature_value' => '10000',  'is_unlimited' => false],
            ['feature_key' => 'saved_lists',        'feature_value' => '100',    'is_unlimited' => false],
            ['feature_key' => 'email_support',      'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'api_access',         'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'bulk_export',        'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'crm_integration',    'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'priority_support',   'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'api_calls',          'feature_value' => '100000', 'is_unlimited' => false],
            ['feature_key' => 'team_members',       'feature_value' => '5',      'is_unlimited' => false],
            ['feature_key' => 'team_analytics',     'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'dedicated_manager',  'feature_value' => 'false',  'is_unlimited' => false],
            ['feature_key' => 'white_label',        'feature_value' => 'false',  'is_unlimited' => false],
        ]);

        // 8. Business – Lifetime
        $bizLifetime = Package::create([
            'name'         => 'Business',
            'slug'         => 'business-lifetime',
            'package_for'  => 'company',
            'billing_type' => 'lifetime',
            'price'        => 2999.00,
            'currency'     => 'USD',
            'max_users'    => 5,
            'is_popular'   => false,
            'description'  => 'Pay once, use forever. Full Business-level access for your team with no recurring fees.',
            'status'       => 'active',
        ]);

        $this->addFeatures($bizLifetime->id, [
            ['feature_key' => 'gmb_searches',      'feature_value' => '10000',  'is_unlimited' => false],
            ['feature_key' => 'leads_per_month',    'feature_value' => '25000',  'is_unlimited' => false],
            ['feature_key' => 'export_leads',       'feature_value' => '10000',  'is_unlimited' => false],
            ['feature_key' => 'saved_lists',        'feature_value' => '100',    'is_unlimited' => false],
            ['feature_key' => 'email_support',      'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'api_access',         'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'bulk_export',        'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'crm_integration',    'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'priority_support',   'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'api_calls',          'feature_value' => '100000', 'is_unlimited' => false],
            ['feature_key' => 'team_members',       'feature_value' => '5',      'is_unlimited' => false],
            ['feature_key' => 'team_analytics',     'feature_value' => 'true',   'is_unlimited' => false],
            ['feature_key' => 'dedicated_manager',  'feature_value' => 'false',  'is_unlimited' => false],
            ['feature_key' => 'white_label',        'feature_value' => 'false',  'is_unlimited' => false],
        ]);
    }

    /**
     * Add features to a package.
     */
    private function addFeatures(int $packageId, array $features): void
    {
        foreach ($features as $feature) {
            PackageFeature::create([
                'package_id'    => $packageId,
                'feature_key'   => $feature['feature_key'],
                'feature_value' => $feature['feature_value'],
                'is_unlimited'  => $feature['is_unlimited'],
            ]);
        }
    }
}
