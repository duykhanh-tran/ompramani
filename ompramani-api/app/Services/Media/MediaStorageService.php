<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\Article;
use App\Models\BuddhistFigure;
use App\Models\CloudinaryMediaMapping;
use App\Models\Mantra;
use App\Models\Product;
use App\Models\RecitationAudio;
use App\Models\Sutra;
use App\Models\Temple;
use App\Models\TempleMedia;
use App\Models\UserProfile;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MediaStorageService
{
    protected ?Cloudinary $cloudinary = null;

    /**
     * Threshold in bytes (20 MB) above which chunked upload is used for audio/video.
     */
    protected const CHUNKED_UPLOAD_THRESHOLD_BYTES = 20971520;

    public function __construct(?array $config = null)
    {
        $this->initializeCloudinary($config);
    }

    /**
     * Initialize Cloudinary client from filesystems disk or environment.
     *
     * @param array<string, mixed>|null $customConfig
     */
    protected function initializeCloudinary(?array $customConfig = null): void
    {
        $diskConfig = $customConfig ?? config('filesystems.disks.cloudinary', []);

        $cloudName = $diskConfig['cloud_name'] ?? null;
        $apiKey = $diskConfig['api_key'] ?? null;
        $apiSecret = $diskConfig['api_secret'] ?? null;
        $url = is_string($diskConfig['url'] ?? null) ? $diskConfig['url'] : null;

        if ($customConfig === null) {
            $cloudName = $cloudName ?: env('CLOUDINARY_CLOUD_NAME');
            $apiKey = $apiKey ?: env('CLOUDINARY_API_KEY');
            $apiSecret = $apiSecret ?: env('CLOUDINARY_API_SECRET');
            $url = $url ?: env('CLOUDINARY_URL');
        }

        if (filled($url)) {
            try {
                $this->cloudinary = new Cloudinary($url);
            } catch (Throwable $e) {
                Log::warning('MediaStorageService: failed to initialize from CLOUDINARY_URL: ' . $e->getMessage());
            }
        } elseif (filled($cloudName) && filled($apiKey) && filled($apiSecret)) {
            try {
                $this->cloudinary = new Cloudinary([
                    'cloud' => [
                        'cloud_name' => $cloudName,
                        'api_key' => $apiKey,
                        'api_secret' => $apiSecret,
                    ],
                    'url' => [
                        'secure' => (bool) ($diskConfig['url']['secure'] ?? env('CLOUDINARY_SECURE_URL', true)),
                    ],
                ]);
            } catch (Throwable $e) {
                Log::warning('MediaStorageService: failed to initialize from cloud config: ' . $e->getMessage());
            }
        }
    }

    /**
     * Check if Cloudinary credentials are fully configured.
     */
    public function isConfigured(): bool
    {
        return $this->cloudinary !== null;
    }

    /**
     * Get the underlying Cloudinary SDK instance.
     */
    public function getCloudinary(): ?Cloudinary
    {
        return $this->cloudinary;
    }

    /**
     * Upload a local file path or UploadedFile to Cloudinary preserving directory structure.
     *
     * @param UploadedFile|string $file Local absolute file path or UploadedFile
     * @param string $logicalPath Relative path (e.g. 'products/item-1.jpg' or 'mantras/audio/chant.mp3')
     * @param array<string, mixed> $extraOptions
     * @return array{
     *     local_path: string,
     *     public_id: string,
     *     resource_type: string,
     *     format: string,
     *     secure_url: string,
     *     checksum: string,
     *     file_size: int,
     *     status: string
     * }
     */
    public function uploadToCloudinary(
        UploadedFile|string $file,
        string $logicalPath,
        array $extraOptions = []
    ): array {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Cloudinary credentials are not configured.');
        }

        $sourcePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! file_exists($sourcePath)) {
            throw new \InvalidArgumentException("Source file does not exist at: [{$sourcePath}]");
        }

        $logicalPath = ltrim($logicalPath, '/');
        $resourceType = MediaUrlResolver::getResourceType($logicalPath);
        $publicId = MediaUrlResolver::extractPublicId($logicalPath);
        $extension = strtolower(pathinfo($logicalPath, PATHINFO_EXTENSION));
        $fileSize = (int) filesize($sourcePath);
        $checksum = (string) hash_file('sha256', $sourcePath);

        // Do NOT overwrite existing resources by default unless explicitly instructed
        $uploadOptions = array_merge([
            'public_id' => $publicId,
            'resource_type' => $resourceType,
            'overwrite' => false,
            'invalidate' => true,
            'use_filename' => false,
            'unique_filename' => false,
        ], $extraOptions);

        $uploadApi = $this->cloudinary->uploadApi();

        // Use chunked upload for large audio/video files to prevent HTTP timeout
        if ($resourceType === 'video' && $fileSize > self::CHUNKED_UPLOAD_THRESHOLD_BYTES) {
            $uploadOptions['chunk_size'] = 6000000; // 6MB chunks
            $response = $uploadApi->uploadChunked($sourcePath, $uploadOptions);
        } else {
            $response = $uploadApi->upload($sourcePath, $uploadOptions);
        }

        $responseData = $response instanceof ApiResponse ? $response->getArrayCopy() : (array) $response;

        $secureUrl = (string) ($responseData['secure_url'] ?? MediaUrlResolver::buildCloudinaryUrl($logicalPath));
        $secureUrl = MediaUrlResolver::cleanDoubleExtension($secureUrl);
        $returnedPublicId = (string) ($responseData['public_id'] ?? $publicId);
        $returnedFormat = (string) ($responseData['format'] ?? $extension);

        // Record or update database mapping
        $mapping = CloudinaryMediaMapping::updateOrCreate(
            ['local_path' => $logicalPath],
            [
                'public_id' => $returnedPublicId,
                'resource_type' => $resourceType,
                'format' => $returnedFormat,
                'secure_url' => $secureUrl,
                'checksum' => $checksum,
                'file_size' => $fileSize,
                'status' => 'synced',
                'error_message' => null,
            ]
        );

        MediaUrlResolver::clearCache();

        return [
            'local_path' => $logicalPath,
            'public_id' => $returnedPublicId,
            'resource_type' => $resourceType,
            'format' => $returnedFormat,
            'secure_url' => $secureUrl,
            'checksum' => $checksum,
            'file_size' => $fileSize,
            'status' => 'synced',
        ];
    }

    /**
     * Check if a given media path is actively used by any record in the system.
     */
    public function isPathUsedByOtherRecords(string $path, ?Model $excludeModel = null): bool
    {
        $clean = ltrim(MediaUrlResolver::cleanDoubleExtension($path), '/');
        if (Str::startsWith($clean, 'storage/')) {
            $clean = substr($clean, 8);
        }

        $variants = array_values(array_unique([$clean, 'storage/' . $clean, '/' . $clean, $path]));

        $isUsed = function ($query, ?Model $exclude): bool {
            if ($exclude && $exclude->exists) {
                $query->where($exclude->getKeyName(), '!=', $exclude->getKey());
            }

            return $query->exists();
        };

        try {
            if ($isUsed(Article::query()->whereIn('cover_image', $variants), $excludeModel instanceof Article ? $excludeModel : null)) {
                return true;
            }
            if ($isUsed(Product::query()->whereIn('cover_image', $variants), $excludeModel instanceof Product ? $excludeModel : null)) {
                return true;
            }
            if ($isUsed(BuddhistFigure::query()->whereIn('thumbnail', $variants), $excludeModel instanceof BuddhistFigure ? $excludeModel : null)) {
                return true;
            }
            if ($isUsed(Sutra::query()->whereIn('thumbnail', $variants), $excludeModel instanceof Sutra ? $excludeModel : null)) {
                return true;
            }
            if ($isUsed(Temple::query()->whereIn('cover_image', $variants), $excludeModel instanceof Temple ? $excludeModel : null)) {
                return true;
            }
            if ($isUsed(TempleMedia::query()->whereIn('file_path', $variants), $excludeModel instanceof TempleMedia ? $excludeModel : null)) {
                return true;
            }
            if ($isUsed(Mantra::query()->whereIn('audio_path', $variants), $excludeModel instanceof Mantra ? $excludeModel : null)) {
                return true;
            }
            if ($isUsed(RecitationAudio::query()->whereIn('file_path', $variants), $excludeModel instanceof RecitationAudio ? $excludeModel : null)) {
                return true;
            }
            if ($isUsed(UserProfile::query()->whereIn('avatar', $variants), $excludeModel instanceof UserProfile ? $excludeModel : null)) {
                return true;
            }
        } catch (Throwable $e) {
            Log::debug('MediaStorageService::isPathUsedByOtherRecords check suppressed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Retrieve asset details directly from Cloudinary Admin API.
     * Returns null if asset does not exist or credentials are not configured.
     *
     * @return array{
     *     public_id: string,
     *     resource_type: string,
     *     format: string,
     *     secure_url: string,
     *     bytes: ?int
     * }|null
     */
    public function getCloudinaryAsset(string $publicId, ?string $resourceType = null): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cleanPublicId = MediaUrlResolver::extractPublicId($publicId);
        $type = $resourceType ?: MediaUrlResolver::getResourceType($publicId);

        // Try exact public_id first, then cleanPublicId if different
        $candidates = array_values(array_unique([$cleanPublicId, $publicId]));

        foreach ($candidates as $id) {
            try {
                $response = $this->cloudinary->adminApi()->asset($id, [
                    'resource_type' => $type,
                ]);

                $data = $response instanceof ApiResponse ? $response->getArrayCopy() : (array) $response;

                return [
                    'public_id' => (string) ($data['public_id'] ?? $id),
                    'resource_type' => (string) ($data['resource_type'] ?? $type),
                    'format' => (string) ($data['format'] ?? pathinfo($id, PATHINFO_EXTENSION)),
                    'secure_url' => (string) ($data['secure_url'] ?? $data['url'] ?? ''),
                    'bytes' => isset($data['bytes']) ? (int) $data['bytes'] : null,
                ];
            } catch (Throwable) {
                // Try next candidate
            }
        }

        return null;
    }

    /**
     * Check if a media asset exists in Cloudinary.
     */
    public function existsInCloudinary(string $logicalPath): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $cleanPath = ltrim($logicalPath, '/');
        $mapping = CloudinaryMediaMapping::query()
            ->where('local_path', $cleanPath)
            ->where('status', 'synced')
            ->first();

        $publicId = $mapping?->public_id ?? MediaUrlResolver::extractPublicId($cleanPath);
        $resourceType = $mapping?->resource_type ?? MediaUrlResolver::getResourceType($cleanPath);

        return $this->getCloudinaryAsset($publicId, $resourceType) !== null;
    }

    /**
     * Delete a media file from Cloudinary and remove its mapping if deletion succeeds.
     * Prevents deletion if the media path is still actively used by another record unless forced.
     */
    public function deleteFromCloudinary(string $logicalPath, bool $force = false, ?Model $ownerModel = null): bool
    {
        $cleanPath = ltrim($logicalPath, '/');

        // Prevent accidental deletion if media is shared by other records
        if (! $force && $this->isPathUsedByOtherRecords($cleanPath, $ownerModel)) {
            Log::info("MediaStorageService: skip deleting [{$cleanPath}] as it is still referenced by other records.");

            return false;
        }

        $mapping = CloudinaryMediaMapping::query()
            ->where('local_path', $cleanPath)
            ->first();

        $publicId = $mapping?->public_id ?? MediaUrlResolver::extractPublicId($cleanPath);
        $resourceType = $mapping?->resource_type ?? MediaUrlResolver::getResourceType($cleanPath);

        $cloudDeleteSucceeded = false;

        if ($this->isConfigured()) {
            try {
                $response = $this->cloudinary->uploadApi()->destroy($publicId, [
                    'resource_type' => $resourceType,
                    'invalidate' => true,
                ]);

                $resArray = $response instanceof ApiResponse ? $response->getArrayCopy() : (array) $response;
                $resultStatus = $resArray['result'] ?? '';

                if ($resultStatus === 'ok' || $resultStatus === 'not found') {
                    $cloudDeleteSucceeded = true;
                } else {
                    Log::warning("Cloudinary destroy returned unexpected status [{$resultStatus}] for asset [{$publicId}]");
                }
            } catch (Throwable $e) {
                Log::warning("Failed to destroy asset [{$publicId}] on Cloudinary: " . $e->getMessage());
            }
        } else {
            Log::warning("Cannot delete asset [{$publicId}] from Cloudinary: Cloudinary is not configured.");
        }

        // Do NOT delete database mapping if Cloudinary deletion failed
        if ($cloudDeleteSucceeded && $mapping) {
            $mapping->delete();
        }

        MediaUrlResolver::clearCache();

        return $cloudDeleteSucceeded;
    }

    /**
     * Store an uploaded file using the active logical public disk (Cloudinary or local).
     */
    public function storePublicFile(
        UploadedFile $file,
        string $directory,
        ?string $customFilename = null
    ): array {
        $publicDisk = (string) config('filesystems.public_disk', 'public');
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
        $filename = $customFilename ?: (string) Str::ulid() . '.' . $extension;
        $logicalPath = trim($directory, '/') . '/' . $filename;

        if ($publicDisk === 'cloudinary' && $this->isConfigured()) {
            return $this->uploadToCloudinary($file, $logicalPath);
        }

        // Store via Laravel Storage disk (local/public/fake)
        $storedPath = Storage::disk($publicDisk)->putFileAs($directory, $file, $filename);
        $secureUrl = Storage::disk($publicDisk)->url($storedPath ?: $logicalPath);

        return [
            'local_path' => $storedPath ?: $logicalPath,
            'public_id' => MediaUrlResolver::extractPublicId($storedPath ?: $logicalPath),
            'resource_type' => MediaUrlResolver::getResourceType($storedPath ?: $logicalPath),
            'format' => $extension,
            'secure_url' => $secureUrl,
            'checksum' => (string) hash_file('sha256', $file->getRealPath()),
            'file_size' => (int) $file->getSize(),
            'status' => 'local',
        ];
    }
}
