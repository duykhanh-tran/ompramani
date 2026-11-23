<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\CloudinaryMediaMapping;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\CloudinaryDiskOptions;
use CodebarAg\FlysystemCloudinary\CloudinaryPathNormalizer;
use CodebarAg\FlysystemCloudinary\CloudinaryResponseLogger;
use CodebarAg\FlysystemCloudinary\CloudinaryResponseMapper;
use CodebarAg\FlysystemCloudinary\CloudinaryStringUploadSource;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Config;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use Throwable;

class CloudinaryStorageAdapter extends FlysystemCloudinaryAdapter
{
    protected CloudinaryPathNormalizer $normalizer;

    protected CloudinaryResponseMapper $responseMapper;

    protected CloudinaryResponseLogger $responseLogger;

    public function __construct(
        Cloudinary $cloudinary,
        ?CloudinaryDiskOptions $diskOptions = null,
    ) {
        parent::__construct($cloudinary, $diskOptions);

        $this->normalizer = new CloudinaryPathNormalizer($diskOptions?->folder);
        $this->responseMapper = new CloudinaryResponseMapper($this->normalizer);
        $this->responseLogger = new CloudinaryResponseLogger();
    }

    /**
     * Upload an object to Cloudinary with automatic public_id sanitization,
     * audio resource_type detection, overwrite protection by default,
     * resilient mapping persistence with retry, and persistent fallback queue.
     *
     * @param string $path
     * @param string|resource $body
     * @param Config $config
     * @return array<string, mixed>|false
     */
    protected function upload(string $path, $body, Config $config): array|false
    {
        $temporaryFileHandle = null;
        $fileForUpload = $body;

        if (is_string($body)) {
            $source = CloudinaryStringUploadSource::create($body);
            if ($source === false) {
                return false;
            }
            $fileForUpload = $source['path'];
            $temporaryFileHandle = $source['handle'];
        }

        $logicalPath = ltrim(trim($path), '/');
        $resourceType = MediaUrlResolver::getResourceType($logicalPath);
        $cleanPublicId = MediaUrlResolver::extractPublicId($logicalPath);
        $extension = strtolower((string) pathinfo($logicalPath, PATHINFO_EXTENSION));

        // Overwrite protection: overwrite is FALSE by default to protect existing assets
        $flyOptions = $config->get('options');
        $allowOverwrite = false;
        if (is_array($flyOptions) && isset($flyOptions['overwrite'])) {
            $allowOverwrite = (bool) $flyOptions['overwrite'];
        } elseif ($config->get('overwrite') !== null) {
            $allowOverwrite = (bool) $config->get('overwrite');
        }

        // Check if an asset already exists in mapping table when overwrite is disabled
        if (! $allowOverwrite) {
            $existingMapping = CloudinaryMediaMapping::query()
                ->where('local_path', $logicalPath)
                ->where('status', 'synced')
                ->first();

            if ($existingMapping) {
                Log::warning("CloudinaryStorageAdapter: duplicate upload prevented for [{$logicalPath}] (asset already exists and overwrite is disabled).");
                throw UnableToWriteFile::atLocation($path, "Asset already exists on Cloudinary for [{$logicalPath}] and overwrite is disabled.");
            }
        }

        // Use clean public_id WITHOUT extension so Cloudinary does not append format twice (.jpg.jpg)
        $uploadOptions = [
            'public_id' => $cleanPublicId,
            'resource_type' => $resourceType,
            'overwrite' => $allowOverwrite,
            'invalidate' => true,
            'use_filename' => false,
            'unique_filename' => false,
        ];

        // Merge any extra flysystem config options while respecting explicit overwrite flag
        if (is_array($flyOptions) && $flyOptions !== []) {
            $uploadOptions = array_merge($uploadOptions, $flyOptions);
            $uploadOptions['overwrite'] = $allowOverwrite;
        }

        try {
            $response = $this->cloudinary
                ->uploadApi()
                ->upload($fileForUpload, $uploadOptions);
        } catch (Throwable $e) {
            Log::error("CloudinaryStorageAdapter: upload failed for [{$logicalPath}]: " . $e->getMessage(), [
                'logicalPath' => $logicalPath,
                'publicId' => $cleanPublicId,
                'resourceType' => $resourceType,
                'overwrite' => $allowOverwrite,
            ]);

            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        } finally {
            if ($temporaryFileHandle !== null) {
                fclose($temporaryFileHandle);
            }
        }

        $responseData = $response instanceof ApiResponse ? $response->getArrayCopy() : (array) $response;
        $returnedPublicId = (string) ($responseData['public_id'] ?? $cleanPublicId);

        // Preserve authentic delivery URL returned by Cloudinary without stripping double extension
        if (isset($responseData['secure_url']) && filled($responseData['secure_url'])) {
            $secureUrl = (string) $responseData['secure_url'];
        } else {
            $secureUrl = (string) MediaUrlResolver::buildCloudinaryUrl($logicalPath);
        }

        $returnedFormat = (string) ($responseData['format'] ?? $extension);
        $bytes = isset($responseData['bytes']) ? (int) $responseData['bytes'] : null;

        // Calculate file checksum if accessible
        $checksum = null;
        if (is_string($fileForUpload) && file_exists($fileForUpload)) {
            $checksum = (string) hash_file('sha256', $fileForUpload);
        } elseif (is_string($body)) {
            $checksum = hash('sha256', $body);
        }

        // Automatically persist database mapping with retry mechanism
        $maxRetries = 3;
        $mappingSaved = false;
        $mappingException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                CloudinaryMediaMapping::updateOrCreate(
                    ['local_path' => $logicalPath],
                    [
                        'public_id' => $returnedPublicId,
                        'resource_type' => $resourceType,
                        'format' => $returnedFormat,
                        'secure_url' => $secureUrl,
                        'checksum' => $checksum,
                        'file_size' => $bytes,
                        'status' => 'synced',
                        'error_message' => null,
                    ]
                );
                MediaUrlResolver::clearCache();
                $mappingSaved = true;
                break;
            } catch (Throwable $e) {
                $mappingException = $e;
                Log::warning("CloudinaryStorageAdapter: mapping save attempt {$attempt}/{$maxRetries} failed for [{$logicalPath}]: " . $e->getMessage());
                if ($attempt < $maxRetries) {
                    usleep(50000 * $attempt); // 50ms, 100ms
                }
            }
        }

        if (! $mappingSaved) {
            // Write recovery entry to persistent fallback queue so reconciliation can restore it
            $this->recordFailedMappingFallback([
                'local_path' => $logicalPath,
                'public_id' => $returnedPublicId,
                'resource_type' => $resourceType,
                'format' => $returnedFormat,
                'secure_url' => $secureUrl,
                'checksum' => $checksum,
                'file_size' => $bytes,
                'failed_at' => now()->toIso8601String(),
                'error' => $mappingException?->getMessage(),
            ]);

            Log::critical("CloudinaryStorageAdapter: CRITICAL failure saving media mapping for [{$logicalPath}] after successful Cloudinary upload. Asset public_id=[{$returnedPublicId}], url=[{$secureUrl}]. Error: " . $mappingException?->getMessage());

            // DO NOT SILENTLY REPORT SUCCESS! Throw UnableToWriteFile so Livewire/Filament shows error
            throw UnableToWriteFile::atLocation(
                $path,
                "Cloudinary upload succeeded, but database mapping persistence failed after {$maxRetries} attempts: " . $mappingException?->getMessage(),
                $mappingException
            );
        }

        $this->responseLogger->log($response);

        return $this->responseMapper->normalizeUploadOrExplicit($response, $logicalPath, $body);
    }

    /**
     * Record a failed mapping persistence payload to disk so it can be restored.
     *
     * @param array<string, mixed> $payload
     */
    protected function recordFailedMappingFallback(array $payload): void
    {
        try {
            $fallbackFile = storage_path('app/pending_media_mappings_recovery.json');
            File::ensureDirectoryExists(dirname($fallbackFile));
            $existing = [];
            if (File::exists($fallbackFile)) {
                $existing = json_decode((string) File::get($fallbackFile), true) ?: [];
            }
            $existing[$payload['local_path']] = $payload;
            File::put($fallbackFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Throwable $e) {
            Log::error("CloudinaryStorageAdapter: failed to write fallback recovery record: " . $e->getMessage());
        }
    }

    /**
     * Resolve delivery URL prioritizing verified secure_url from mapping.
     */
    public function getUrl(string|array $path): string|false
    {
        $rawPath = is_array($path) ? ($path['path'] ?? '') : $path;
        $logicalPath = ltrim(trim((string) $rawPath), '/');

        if (blank($logicalPath)) {
            return false;
        }

        // 1. Check database mapping
        try {
            $mapping = CloudinaryMediaMapping::query()
                ->where('local_path', $logicalPath)
                ->where('status', 'synced')
                ->first();

            if ($mapping && filled($mapping->secure_url)) {
                return (string) $mapping->secure_url;
            }
        } catch (Throwable) {
            // Fall through if database is unreachable
        }

        // 2. Build exact Cloudinary URL with correct resource_type
        $builtUrl = MediaUrlResolver::buildCloudinaryUrl($logicalPath);
        if ($builtUrl) {
            return $builtUrl;
        }

        // 3. Fallback to parent adapter
        return parent::getUrl($path);
    }

    /**
     * Fast file existence check via local mapping database first.
     */
    public function fileExists(string $path): bool
    {
        $logicalPath = ltrim(trim($path), '/');

        try {
            $existsInDb = CloudinaryMediaMapping::query()
                ->where('local_path', $logicalPath)
                ->where('status', 'synced')
                ->exists();

            if ($existsInDb) {
                return true;
            }
        } catch (Throwable) {
            // Ignore DB error
        }

        return parent::fileExists($path);
    }

    /**
     * Delete file from Cloudinary and clean up mapping.
     * Prevents deletion if the media path is still actively used by another record.
     * Only deletes mapping when Cloudinary confirms deletion was successful.
     */
    public function delete(string $path): void
    {
        $logicalPath = ltrim(trim($path), '/');
        $cleanPublicId = MediaUrlResolver::extractPublicId($logicalPath);
        $resourceType = MediaUrlResolver::getResourceType($logicalPath);

        // 1. Protect asset if actively referenced by other database records!
        /** @var MediaStorageService $storageService */
        $storageService = app(MediaStorageService::class);
        if ($storageService->isPathUsedByOtherRecords($logicalPath)) {
            Log::info("CloudinaryStorageAdapter: skipped deleting [{$logicalPath}] as it is actively referenced by other records.");

            return;
        }

        try {
            $mapping = CloudinaryMediaMapping::query()
                ->where('local_path', $logicalPath)
                ->first();

            $publicId = $mapping?->public_id ?? $cleanPublicId;
            $type = $mapping?->resource_type ?? $resourceType;

            $response = $this->cloudinary->uploadApi()->destroy($publicId, [
                'resource_type' => $type,
                'invalidate' => true,
            ]);

            $resArray = $response instanceof ApiResponse ? $response->getArrayCopy() : (array) $response;
            $resultStatus = (string) ($resArray['result'] ?? '');

            // ONLY delete database mapping if Cloudinary confirms deletion succeeded
            if ($resultStatus === 'ok' || $resultStatus === 'not found') {
                if ($mapping) {
                    $mapping->delete();
                }
                MediaUrlResolver::clearCache();
            } else {
                Log::error("CloudinaryStorageAdapter: destroy rejected by Cloudinary for [{$publicId}], status: [{$resultStatus}]");
                throw UnableToDeleteFile::atLocation($path, "Cloudinary destroy rejected with status: [{$resultStatus}]");
            }
        } catch (Throwable $e) {
            Log::warning("CloudinaryStorageAdapter: delete failed for [{$logicalPath}]: " . $e->getMessage());
            if ($e instanceof UnableToDeleteFile) {
                throw $e;
            }
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }
}
