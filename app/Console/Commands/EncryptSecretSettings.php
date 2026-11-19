<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts legacy plaintext secrets in the `settings` table (rows with
 * type "password": Resend key, Google API keys, OAuth client secret...).
 *
 * Safe to run more than once: rows that are already encrypted are skipped.
 * Dry-run by default; pass --force to write. Values are never printed.
 * Take a database backup first — encrypted values depend on APP_KEY.
 */
class EncryptSecretSettings extends Command
{
    protected $signature = 'settings:encrypt-secrets {--force : Actually write the changes}
                                                    {--decrypt : Reverse: turn encrypted secrets back into plaintext (only for rolling back to an older release)}';

    protected $description = 'Encrypt legacy plaintext secret settings (type=password) with APP_KEY';

    public function handle(): int
    {
        if ($this->option('decrypt')) {
            return $this->decryptAll();
        }

        $rows = Setting::where('type', Setting::TYPE_SECRET)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->get();

        $plaintext = $rows->reject(fn (Setting $s) => Setting::looksEncrypted($s->value));

        $this->info("Secret settings: {$rows->count()} total, {$plaintext->count()} still plaintext.");
        foreach ($plaintext as $setting) {
            $this->line("  - {$setting->key}");
        }

        if ($plaintext->isEmpty()) {
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            $this->warn('Dry run only. Back up the database, then re-run with --force.');
            return self::SUCCESS;
        }

        foreach ($plaintext as $setting) {
            // Write the column directly so the plaintext value is never re-processed
            Setting::whereKey($setting->id)->update(['value' => Crypt::encryptString($setting->value)]);
            Cache::forget("setting_{$setting->key}");
        }

        $this->info("Encrypted {$plaintext->count()} setting(s).");

        return self::SUCCESS;
    }

    /**
     * Rollback helper: decrypt every encrypted secret back to plaintext so an older
     * release (which does not decrypt) keeps working.
     */
    private function decryptAll(): int
    {
        $encrypted = Setting::where('type', Setting::TYPE_SECRET)->get()
            ->filter(fn (Setting $s) => Setting::looksEncrypted($s->value));

        $this->info("Encrypted secret settings: {$encrypted->count()}.");

        if ($encrypted->isEmpty() || !$this->option('force')) {
            if ($encrypted->isNotEmpty()) {
                $this->warn('Dry run only. Re-run with --decrypt --force to write plaintext values.');
            }
            return self::SUCCESS;
        }

        foreach ($encrypted as $setting) {
            Setting::whereKey($setting->id)->update(['value' => Crypt::decryptString($setting->value)]);
            Cache::forget("setting_{$setting->key}");
        }

        $this->info("Decrypted {$encrypted->count()} setting(s).");

        return self::SUCCESS;
    }
}
