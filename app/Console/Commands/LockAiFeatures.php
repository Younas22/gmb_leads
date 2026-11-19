<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Models\PackageFeature;
use App\Services\FeatureOverrides;
use App\Support\PlanFeatureCatalog;
use Illuminate\Console\Command;

/**
 * Lock (or unlock) whole feature groups in every package, with a message users see instead.
 * Same effect as locking each feature in Admin > Packages > Features. Values are kept, so
 * unlocking restores each plan exactly as it was.
 *
 *   php artisan plan:coming-soon                         (lock AI + email + Resend, "Coming soon")
 *   php artisan plan:coming-soon --group=ai              (only AI)
 *   php artisan plan:coming-soon --message="Launching in May"
 *   php artisan plan:coming-soon --unlock                (open them again)
 *   php artisan plan:lock-ai                             (AI only; see LockAiOnly)
 */
class LockAiFeatures extends Command
{
    protected $signature = 'plan:coming-soon
        {--group=* : Feature groups to lock: ai, email, resend (default: all three)}
        {--message=Coming soon : Message shown to users on the locked features}
        {--unlock : Remove the lock instead}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Lock or unlock the AI, email and Resend features in every package (shown as "Coming soon").';

    public function handle(): int
    {
        if (!FeatureOverrides::installed()) {
            $this->error('Feature locks are not installed yet. Run "php artisan migrate --force" first.');
            return self::FAILURE;
        }

        $groups = (array) $this->option('group') ?: ['ai', 'email', 'resend'];
        $unknown = array_diff($groups, array_keys(PlanFeatureCatalog::GROUP_GATE));
        if ($unknown) {
            $this->error('Unknown group(s): ' . implode(', ', $unknown) . '. Use ai, email or resend.');
            return self::FAILURE;
        }

        $keys = [];
        foreach ($groups as $group) {
            $keys = array_merge($keys, PlanFeatureCatalog::keysOfGroup($group));
        }

        $unlock = (bool) $this->option('unlock');
        $message = trim((string) $this->option('message')) ?: 'Coming soon';
        $dry = (bool) $this->option('dry-run');
        $rows = [];

        foreach (Package::orderBy('id')->get() as $package) {
            foreach ($keys as $key) {
                $feature = PackageFeature::firstOrNew(['package_id' => $package->id, 'feature_key' => $key]);
                $was = $feature->exists ? ($feature->is_locked ? 'locked' : 'open') : 'missing';

                if (!$feature->exists) {
                    // Keep the app default so unlocking later gives the normal plan value
                    $default = config("ai.feature_defaults.{$key}");
                    $feature->feature_value = is_bool($default) ? ($default ? '1' : '0') : (string) ($default ?? '0');
                    $feature->is_unlimited = false;
                }
                $feature->is_locked = !$unlock;
                $feature->lock_message = $unlock ? null : $message;

                if (!$dry) {
                    $feature->save();
                }
                $rows[] = [$package->id, $package->name, $package->billing_type, $key, $was, $unlock ? 'open' : 'locked'];
            }
        }

        $this->table(['#', 'Package', 'Billing', 'Feature', 'Before', 'After'], $rows);
        if ($dry) {
            $this->warn('Dry run: nothing was saved.');
        } else {
            PlanFeatureCatalog::flushComingSoon();
            $this->info(($unlock ? 'Unlocked' : "Locked with the message \"{$message}\"") . ': ' . implode(', ', $groups) . ' features in every package.');
        }

        return self::SUCCESS;
    }
}
