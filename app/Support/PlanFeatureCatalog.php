<?php

namespace App\Support;

use App\Models\Package;

/**
 * One place that describes every package feature for the public pricing (home), the
 * subscription page cards and the comparison tables. Values stored in package_features
 * are not uniform: older switches use 'true'/'false', newer ones '1'/'0', limits use
 * numbers or 'unlimited' (or is_unlimited). Everything is read through here.
 */
final class PlanFeatureCatalog
{
    public const GROUPS = [
        'leads' => 'Lead generation',
        'ai' => 'AI outreach',
        'email' => 'Email sending & tracking',
        'resend' => 'Resend & campaigns',
        'account' => 'Account & support',
    ];

    /** key => [name, desc, type bool|value, suffix, group] */
    public const FEATURES = [
        'unlimited_map_scraping' => ['Unlimited Map Scraping', 'Scrape as many Google Maps results as you need', 'bool', '', 'leads'],
        'daily_leads_limit' => ['Daily Leads Limit', 'Max leads you can collect per day', 'value', '/day', 'leads'],
        'basic_business_signals' => ['Basic Business Signals', 'Name, address, category, rating, total reviews', 'bool', '', 'leads'],
        'contact_ready_leads' => ['Contact-Ready Leads', 'Verified phone & website data included', 'bool', '', 'leads'],
        'email_scraping' => ['Email Scraping', 'Extract emails from business websites', 'bool', '', 'leads'],
        'social_media_scraping' => ['Social Media Scraping', 'Facebook, Instagram, Twitter & more', 'bool', '', 'leads'],
        'website_extraction' => ['Website Extraction', 'Pull full website URL from listings', 'bool', '', 'leads'],
        'latest_review_insights' => ['Latest Review Insights', 'Recent review text & sentiment signals', 'bool', '', 'leads'],
        'advanced_review_filters' => ['Advanced Review Filters', 'Filter by rating, recency & keywords', 'bool', '', 'leads'],
        'export_leads' => ['Export Leads', 'Download leads as CSV / Excel', 'value', '/month', 'leads'],

        'ai_sales_profile_enabled' => ['AI Sales Profile', 'Describe your business once; AI uses it in every email', 'bool', '', 'ai'],
        'ai_audits_monthly' => ['AI Website Analyses', 'AI checks each lead\'s website and matches your offer', 'value', '/month', 'ai'],
        'ai_email_drafts_monthly' => ['AI Email Drafts', 'Personalised outreach emails written by AI', 'value', '/month', 'ai'],
        'ai_offers_monthly' => ['AI Offer Builder', 'Complete marketing offers from 16 proven strategies', 'value', '/month', 'ai'],

        'gmail_sending_enabled' => ['Send from Gmail', 'Send approved emails from your own Gmail account', 'bool', '', 'email'],
        'email_sends_daily' => ['Gmail Emails per Day', 'One-to-one emails sent from your Gmail', 'value', '/day', 'email'],
        'email_tracking_enabled' => ['Link Click Tracking', 'See which links your leads opened', 'bool', '', 'email'],
        'email_analytics_enabled' => ['Email Analytics', 'Delivery, replies and results in one dashboard', 'bool', '', 'email'],
        'email_followups_enabled' => ['Follow-up Reminders', 'Never forget to follow up with a lead', 'bool', '', 'email'],

        'resend_connection_enabled' => ['Resend Integration', 'Connect your own Resend account and domain', 'bool', '', 'resend'],
        'resend_single_sending_enabled' => ['Send via Resend', 'One-to-one emails through your Resend domain', 'bool', '', 'resend'],
        'resend_campaigns_enabled' => ['Email Campaigns', 'Campaigns to your opted-in contacts and lead folders', 'bool', '', 'resend'],
        'resend_emails_monthly' => ['Resend Emails per Month', 'Emails sent through your Resend account', 'value', '/month', 'resend'],
        'resend_contacts_limit' => ['Opted-in Contacts', 'Contacts you can keep for campaigns', 'value', '', 'resend'],
        'resend_campaigns_monthly' => ['Campaigns per Month', 'Campaigns you can send each month', 'value', '/month', 'resend'],

        'max_devices' => ['Devices Access', 'Number of devices that can access your account', 'value', '', 'account'],
        'priority_support' => ['Priority Support', 'Fast-track support response', 'bool', '', 'account'],
    ];

    /** Shown on the pricing cards (the rest is in the comparison table). */
    public const CARD = ['daily_leads_limit', 'export_leads', 'ai_audits_monthly', 'ai_email_drafts_monthly', 'gmail_sending_enabled', 'resend_campaigns_enabled', 'max_devices', 'priority_support'];

    private const ON = ['true', '1', 'yes', 'on', 'unlimited'];

    /**
     * Value of one feature in a package, in the shape the existing templates expect:
     * 'true' (switch on) | 'unlimited' | '<number>' | null (not included).
     * A feature the package does not list falls back to the app default (config/ai.php).
     */
    public static function legacyValue(Package $package, string $key): ?string
    {
        $row = $package->features->firstWhere('feature_key', $key);
        if ($row?->is_locked) {
            return null; // locked by the admin: not included
        }
        $raw = $row ? $row->feature_value : (array_key_exists($key, (array) config('ai.feature_defaults')) ? config("ai.feature_defaults.{$key}") : null);
        $unlimited = ($row?->is_unlimited) || (is_string($raw) && strtolower($raw) === 'unlimited') || $raw === -1 || $raw === '-1';
        $type = self::FEATURES[$key][2] ?? 'bool';

        if ($type === 'bool') {
            return ($unlimited || $raw === true || in_array(strtolower(trim((string) $raw)), self::ON, true)) ? 'true' : null;
        }
        if ($unlimited) {
            return 'unlimited';
        }

        return is_numeric($raw) && (int) $raw > 0 ? (string) (int) $raw : null;
    }

    /** Rows for comparison tables: group headings ('type' => 'group') followed by their features. */
    public static function comparisonRows(): array
    {
        $rows = [];
        foreach (self::GROUPS as $group => $title) {
            $rows['__group_' . $group] = ['name' => $title, 'type' => 'group'];
            foreach (self::FEATURES as $key => [$name, $desc, $type, $suffix, $g]) {
                if ($g === $group) {
                    $rows[$key] = ['name' => $name, 'desc' => $desc, 'type' => $type, 'suffix' => $suffix];
                }
            }
        }

        return $rows;
    }

    /** Pricing card lines: [label, included(bool), value text or null, locked by the admin (bool)]. */
    public static function cardLines(Package $package): array
    {
        $lines = [];
        foreach (self::CARD as $key) {
            [$name, , $type, $suffix] = self::FEATURES[$key];
            $val = self::legacyValue($package, $key);
            $text = match (true) {
                $type === 'bool' || $val === null => null,
                $val === 'unlimited' => 'Unlimited',
                $key === 'max_devices' => $val . ' ' . ((int) $val === 1 ? 'Device' : 'Devices'),
                default => number_format((int) $val) . $suffix,
            };
            $lines[] = [$name, $val !== null, $text, self::isLocked($package, $key)];
        }

        return $lines;
    }

    /** The AI features (the "AI outreach" group). */
    public const AI_KEYS = ['ai_sales_profile_enabled', 'ai_audits_monthly', 'ai_email_drafts_monthly', 'ai_offers_monthly'];

    /** Whether the admin locked this feature in the package (shown as "Coming soon" on pricing). */
    public static function isLocked(Package $package, string $key): bool
    {
        return (bool) $package->features->firstWhere('feature_key', $key)?->is_locked;
    }

    /** The feature that decides whether a whole group counts as "coming soon". */
    public const GROUP_GATE = [
        'ai' => 'ai_sales_profile_enabled',
        'email' => 'gmail_sending_enabled',
        'resend' => 'resend_connection_enabled',
    ];

    /** The feature keys of one group (see GROUPS). */
    public static function keysOfGroup(string $group): array
    {
        return array_keys(array_filter(self::FEATURES, fn ($meta) => $meta[4] === $group));
    }

    /** Answers remembered for the current request only (keyed by the request object). */
    private static array $comingSoon = [];
    private static ?int $comingSoonRequest = null;

    /**
     * True when the admin has locked a group's main feature in every package that defines it:
     * the public pages then present that group as "coming soon". Unlocking it in any package
     * makes it show as available again.
     */
    public static function comingSoon(string $group): bool
    {
        $request = spl_object_id(request());
        if ($request !== self::$comingSoonRequest) {
            self::$comingSoon = [];
            self::$comingSoonRequest = $request;
        }
        if (array_key_exists($group, self::$comingSoon)) {
            return self::$comingSoon[$group];
        }
        $gate = self::GROUP_GATE[$group] ?? null;
        if (!$gate || !\App\Services\FeatureOverrides::installed()) {
            return self::$comingSoon[$group] = false;
        }
        $rows = \App\Models\PackageFeature::where('feature_key', $gate);

        return self::$comingSoon[$group] = (clone $rows)->exists() && !(clone $rows)->where('is_locked', false)->exists();
    }

    public static function aiComingSoon(): bool
    {
        return self::comingSoon('ai');
    }

    /** Forget the remembered answers (after locking/unlocking in the same process). */
    public static function flushComingSoon(): void
    {
        self::$comingSoon = [];
        self::$comingSoonRequest = null;
    }

    /** @deprecated use flushComingSoon() */
    public static function flushAiComingSoon(): void
    {
        self::flushComingSoon();
    }
}
