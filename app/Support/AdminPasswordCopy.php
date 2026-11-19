<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-viewable copy of a user's password, stored ENCRYPTED (APP_KEY) in users.password_copy.
 * Written on register / login / reset / change; read only by admins on /admin/users.
 * Changing APP_KEY makes old copies unreadable (they refill on the user's next login).
 */
class AdminPasswordCopy
{
    public static function store(User $user, ?string $plain): void
    {
        if ($plain === null || $plain === '' || !self::installed()) {
            return;
        }

        try {
            DB::table('users')->where('id', $user->id)->update(['password_copy' => Crypt::encryptString($plain)]);
        } catch (\Throwable $e) {
            Log::warning('Password copy not saved', ['user_id' => $user->id, 'exception' => get_class($e)]);
        }
    }

    public static function reveal(User $user): ?string
    {
        if (!self::installed()) {
            return null;
        }
        $value = DB::table('users')->where('id', $user->id)->value('password_copy');
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function installed(): bool
    {
        static $has = null;

        return $has ??= Schema::hasColumn('users', 'password_copy');
    }
}
