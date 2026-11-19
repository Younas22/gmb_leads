<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Safe file storage for uploads.
 *
 * - The stored name is random; the client-supplied name and extension are never used.
 * - The extension is derived from the file's real content (MIME sniffing) and must
 *   be on an allow-list, so "shell.php" renamed to look like an image cannot be
 *   stored with an executable extension.
 */
class SecureUpload
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Store an image inside public_path($directory) (for files that must be publicly
     * reachable, e.g. avatars and site logos). Returns the relative path.
     */
    public static function storePublicImage(UploadedFile $file, string $directory, array $allowedExtensions = self::IMAGE_EXTENSIONS): string
    {
        $extension = self::safeExtension($file, $allowedExtensions);
        $name = Str::random(40) . '.' . $extension;

        $file->move(public_path($directory), $name);

        return trim($directory, '/') . '/' . $name;
    }

    /**
     * Store a file on the private "local" disk (storage/app/private), which is not
     * web-accessible. Returns the path relative to that disk.
     */
    public static function storePrivate(UploadedFile $file, string $directory, array $allowedExtensions = self::IMAGE_EXTENSIONS): string
    {
        $extension = self::safeExtension($file, $allowedExtensions);
        $name = Str::random(40) . '.' . $extension;

        $path = Storage::disk('local')->putFileAs(trim($directory, '/'), $file, $name);
        if ($path === false) {
            throw new RuntimeException('Could not store the uploaded file.');
        }

        return $path;
    }

    private static function safeExtension(UploadedFile $file, array $allowedExtensions): string
    {
        $extension = strtolower((string) $file->guessExtension());
        if ($extension === 'jpe') {
            $extension = 'jpg';
        }

        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('This file type is not allowed.');
        }

        return $extension;
    }
}
