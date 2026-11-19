<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class Setting extends Model
{
    use HasFactory;

    /** Settings of this type hold secrets (API keys, OAuth secrets) and are stored encrypted. */
    public const TYPE_SECRET = 'password';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description'
    ];

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        $setting = Cache::remember("setting_{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        // Parse value based on type
        return self::parseValue($setting->value, $setting->type, $key);
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value, $type = 'text', $group = 'general', $description = null)
    {
        $stored = is_array($value) ? json_encode($value) : $value;

        // Secrets are encrypted at rest with APP_KEY (the cache only ever holds the encrypted form)
        if ($type === self::TYPE_SECRET && is_string($stored) && $stored !== '') {
            $stored = Crypt::encryptString($stored);
        }

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'type' => $type,
                'group' => $group,
                'description' => $description
            ]
        );

        Cache::forget("setting_{$key}");

        return $setting;
    }

    /**
     * Parse value based on type
     */
    protected static function parseValue($value, $type, ?string $key = null)
    {
        switch ($type) {
            case self::TYPE_SECRET:
                return self::decryptSecret($value, $key);
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            default:
                return $value;
        }
    }

    /**
     * Decrypt a secret. Legacy rows saved before encryption was introduced are
     * still plaintext and are returned unchanged (run `settings:encrypt-secrets`
     * to convert them). A value that IS encrypted but cannot be decrypted (e.g.
     * APP_KEY changed) returns null instead of leaking ciphertext as a "key".
     */
    public static function decryptSecret($value, ?string $key = null)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!self::looksEncrypted($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            Log::warning('Could not decrypt a secret setting (APP_KEY changed?). Re-enter it in admin settings.', ['key' => $key]);
            return null;
        }
    }

    /** True when the value has the shape of a Laravel encrypted payload. */
    public static function looksEncrypted($value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']);
    }

    /**
     * Get all settings by group (secrets decrypted)
     */
    public static function getByGroup($group)
    {
        return self::where('group', $group)->get()->mapWithKeys(
            fn (self $setting) => [$setting->key => $setting->type === self::TYPE_SECRET
                ? self::decryptSecret($setting->value, $setting->key)
                : $setting->value]
        );
    }
}
