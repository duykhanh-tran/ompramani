<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\CloudinaryMediaMapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUrlResolver
{
    /**
     * In-memory cache for resolved URLs during the request lifecycle.
     *
     * @var array<string, string>
     */
    protected static array $resolvedCache = [];

    /**
     * Audio file extensions (Cloudinary requires resource_type=video for audio).
     */
    protected const AUDIO_EXTENSIONS = [
        'mp3',
        'wav',
        'm4a',
        'aac',
        'ogg',
        'flac',
        'opus',
        'wma',
    ];

    /**
     * Image file extensions.
     */
    protected const IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'svg',
        'avif',
        'bmp',
    ];

    /**
     * Clean any duplicate file extension suffix (e.g. .jpg.jpg -> .jpg, .png.png -> .png, .mp3.mp3 -> .mp3).
     */
    public static function cleanDoubleExtension(string $urlOrPath): string
    {
        return (string) preg_replace('/(\.[a-zA-Z0-9]{3,4})\1$/i', '$1', $urlOrPath);
    }

    /**
     * Resolve any media path or URL to a clean, absolute HTTPS URL.
     */
    public static function resolve(?string $path, ?string $fallback = null): ?string
    {
        if (blank($path)) {
            return $fallback;
        }

        $path = trim((string) $path);

        // 1. Sanitize any duplicate extension (.jpg.jpg -> .jpg)
        $cleanPath = static::cleanDoubleExtension($path);

        // 2. Return already fully-qualified or data URLs immediately
        if (Str::startsWith($cleanPath, ['http://', 'https://', 'data:'])) {
            return $cleanPath;
        }

        if (Str::startsWith($cleanPath, '//')) {
            return 'https:' . $cleanPath;
        }

        // 3. Normalize path: strip leading slashes and common prefixes like 'storage/' or 'public/'
        $normalized = ltrim($cleanPath, '/');
        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        } elseif (Str::startsWith($normalized, 'public/')) {
            $normalized = substr($normalized, 7);
        }

        if (isset(static::$resolvedCache[$normalized])) {
            return static::$resolvedCache[$normalized];
        }

        // 4. Priority: Check database mapping table for confirmed synced Cloudinary media
        try {
            $mapping = CloudinaryMediaMapping::query()
                ->where(function ($query) use ($normalized) {
                    $query->where('local_path', $normalized)
                        ->orWhere('local_path', 'storage/' . $normalized)
                        ->orWhere('local_path', 'public/' . $normalized);
                })
                ->where('status', 'synced')
                ->first();

            if ($mapping && filled($mapping->secure_url)) {
                return static::$resolvedCache[$normalized] = (string) $mapping->secure_url;
            }
        } catch (\Throwable $e) {
            Log::warning('MediaUrlResolver: failed to query cloudinary_media_mappings table: ' . $e->getMessage(), [
                'path' => $normalized,
            ]);
        }

        // 5. If public disk is configured to an external cloud disk (like r2_public or s3), resolve via that disk
        $publicDisk = (string) config('filesystems.public_disk', 'public');
        if ($publicDisk !== 'public' && $publicDisk !== 'cloudinary') {
            try {
                $storageUrl = Storage::disk($publicDisk)->url($normalized);

                return static::$resolvedCache[$normalized] = $storageUrl;
            } catch (\Throwable) {
                // Ignore and fall through to legacy fallback
            }
        }

        // 6. If local file exists in storage/app/public, return its public storage URL as safe fallback
        $localFile = storage_path('app/public/' . $normalized);
        if (file_exists($localFile) && is_file($localFile)) {
            return static::$resolvedCache[$normalized] = Storage::disk('public')->url($normalized);
        }

        // 7. Fallback: If not yet synced to Cloudinary, do NOT fabricate an unverified Cloudinary URL.
        // Return the legacy path directly so existing API contracts and frontend resolver remain 100% backwards compatible.
        return static::$resolvedCache[$normalized] = $cleanPath;
    }

    /**
     * Build an exact Cloudinary delivery URL respecting resource_type (image vs video/audio).
     */
    public static function buildCloudinaryUrl(string $path): ?string
    {
        $cloudName = config('filesystems.disks.cloudinary.cloud_name')
            ?: env('CLOUDINARY_CLOUD_NAME');

        if (empty($cloudName) && env('CLOUDINARY_URL')) {
            try {
                $parsed = parse_url(env('CLOUDINARY_URL'));
                $cloudName = isset($parsed['host']) ? $parsed['host'] : null;
            } catch (\Throwable) {
                $cloudName = null;
            }
        }

        if (empty($cloudName)) {
            return null;
        }

        $resourceType = static::getResourceType($path);
        $cleanPath = ltrim(static::cleanDoubleExtension($path), '/');

        return sprintf(
            'https://res.cloudinary.com/%s/%s/upload/%s',
            $cloudName,
            $resourceType,
            $cleanPath
        );
    }

    /**
     * Detect whether a path is an audio file.
     */
    public static function isAudio(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::AUDIO_EXTENSIONS, true);
    }

    /**
     * Detect whether a path is an image file.
     */
    public static function isImage(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::IMAGE_EXTENSIONS, true);
    }

    /**
     * Get Cloudinary resource_type for a file ('image', 'video', or 'raw').
     */
    public static function getResourceType(string $path): string
    {
        if (static::isAudio($path)) {
            return 'video'; // Cloudinary treats audio (mp3, wav, etc.) under the video endpoint
        }

        if (static::isImage($path)) {
            return 'image';
        }

        return 'raw';
    }

    /**
     * Extract a clean public_id from a storage path (without file extension).
     */
    public static function extractPublicId(string $path): string
    {
        $clean = ltrim($path, '/');
        $info = pathinfo($clean);
        $dirname = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'] . '/';

        return $dirname . $info['filename'];
    }

    /**
     * Clear in-memory URL resolution cache.
     */
    public static function clearCache(): void
    {
        static::$resolvedCache = [];
    }
}
