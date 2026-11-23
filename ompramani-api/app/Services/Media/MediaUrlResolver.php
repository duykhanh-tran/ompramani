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
     * Preserves authentic Cloudinary delivery URLs (even if they contain .jpg.jpg).
     */
    public static function resolve(?string $path, ?string $fallback = null): ?string
    {
        if (blank($path)) {
            return $fallback;
        }

        $path = trim((string) $path);

        // 1. Return already fully-qualified or data URLs immediately without altering genuine suffixes
        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        if (Str::startsWith($path, '//')) {
            return 'https:' . $path;
        }

        // 2. Normalize path: strip leading slashes and common prefixes like 'storage/' or 'public/'
        $rawNormalized = ltrim($path, '/');
        if (Str::startsWith($rawNormalized, 'storage/')) {
            $rawNormalized = substr($rawNormalized, 8);
        } elseif (Str::startsWith($rawNormalized, 'public/')) {
            $rawNormalized = substr($rawNormalized, 7);
        }

        if (isset(static::$resolvedCache[$rawNormalized])) {
            return static::$resolvedCache[$rawNormalized];
        }

        $cleanNormalized = static::cleanDoubleExtension($rawNormalized);

        // 3. Priority: Check database mapping table for confirmed synced Cloudinary media
        try {
            $mapping = CloudinaryMediaMapping::query()
                ->where(function ($query) use ($rawNormalized, $cleanNormalized) {
                    $query->where('local_path', $rawNormalized)
                        ->orWhere('local_path', 'storage/' . $rawNormalized)
                        ->orWhere('local_path', 'public/' . $rawNormalized);
                    if ($cleanNormalized !== $rawNormalized) {
                        $query->orWhere('local_path', $cleanNormalized)
                            ->orWhere('local_path', 'storage/' . $cleanNormalized)
                            ->orWhere('local_path', 'public/' . $cleanNormalized);
                    }
                })
                ->where('status', 'synced')
                ->first();

            if ($mapping && filled($mapping->secure_url)) {
                return static::$resolvedCache[$rawNormalized] = (string) $mapping->secure_url;
            }
        } catch (\Throwable $e) {
            Log::warning('MediaUrlResolver: failed to query cloudinary_media_mappings table: ' . $e->getMessage(), [
                'path' => $rawNormalized,
            ]);
        }

        // 4. If public disk is configured to an external cloud disk (like r2_public or s3), resolve via that disk
        $publicDisk = (string) config('filesystems.public_disk', 'public');
        if ($publicDisk !== 'public' && $publicDisk !== 'cloudinary') {
            try {
                $storageUrl = Storage::disk($publicDisk)->url($cleanNormalized);

                return static::$resolvedCache[$rawNormalized] = $storageUrl;
            } catch (\Throwable) {
                // Ignore and fall through to legacy fallback
            }
        }

        // 5. If local file exists in storage/app/public, return its public storage URL as safe fallback
        $localFile = storage_path('app/public/' . $cleanNormalized);
        if (file_exists($localFile) && is_file($localFile)) {
            return static::$resolvedCache[$rawNormalized] = Storage::disk('public')->url($cleanNormalized);
        }

        // 6. Fallback: If not yet synced to Cloudinary, do NOT fabricate an unverified Cloudinary URL.
        // Return the clean path directly so existing API contracts and frontend resolver remain 100% backwards compatible.
        return static::$resolvedCache[$rawNormalized] = $cleanNormalized;
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
