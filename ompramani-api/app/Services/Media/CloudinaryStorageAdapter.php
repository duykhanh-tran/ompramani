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
     * Upload an object to Cloudinary with automatic public_id sanitization
     * (preventing double extension .jpg.jpg), audio resource_type detection,
     * and automatic database mapping persistence.
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

        // Use clean public_id WITHOUT extension so Cloudinary does not append format twice (.jpg.jpg)
        $uploadOptions = [
            'public_id' => $cleanPublicId,
            'resource_type' => $resourceType,
            'overwrite' => true,
            'invalidate' => true,
            'use_filename' => false,
            'unique_filename' => false,
        ];

        // Merge any extra flysystem config options
        $flyOptions = $config->get('options');
        if (is_array($flyOptions) && $flyOptions !== []) {
            $uploadOptions = array_merge($uploadOptions, $flyOptions);
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
            ]);

            return false;
        } finally {
            if ($temporaryFileHandle !== null) {
                fclose($temporaryFileHandle);
            }
        }

        $responseData = $response instanceof ApiResponse ? $response->getArrayCopy() : (array) $response;
        $returnedPublicId = (string) ($responseData['public_id'] ?? $cleanPublicId);
        $secureUrl = (string) ($responseData['secure_url'] ?? MediaUrlResolver::buildCloudinaryUrl($logicalPath));
        $returnedFormat = (string) ($responseData['format'] ?? $extension);
        $bytes = isset($responseData['bytes']) ? (int) $responseData['bytes'] : null;

        // Clean any double extensions from secure_url (e.g. .jpg.jpg -> .jpg)
        $secureUrl = MediaUrlResolver::cleanDoubleExtension($secureUrl);

        // Calculate file checksum if accessible
        $checksum = null;
        if (is_string($fileForUpload) && file_exists($fileForUpload)) {
            $checksum = (string) hash_file('sha256', $fileForUpload);
        } elseif (is_string($body)) {
            $checksum = hash('sha256', $body);
        }

        // Automatically update or create database mapping in Neon
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
        } catch (Throwable $e) {
            Log::warning("CloudinaryStorageAdapter: failed to record mapping for [{$logicalPath}]: " . $e->getMessage());
        }

        $this->responseLogger->log($response);

        return $this->responseMapper->normalizeUploadOrExplicit($response, $logicalPath, $body);
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
                return MediaUrlResolver::cleanDoubleExtension((string) $mapping->secure_url);
            }
        } catch (Throwable) {
            // Fall through if database is unreachable
        }

        // 2. Build exact Cloudinary URL with correct resource_type and no double extension
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
     */
    public function delete(string $path): void
    {
        $logicalPath = ltrim(trim($path), '/');
        $cleanPublicId = MediaUrlResolver::extractPublicId($logicalPath);
        $resourceType = MediaUrlResolver::getResourceType($logicalPath);

        try {
            $mapping = CloudinaryMediaMapping::query()
                ->where('local_path', $logicalPath)
                ->first();

            $publicId = $mapping?->public_id ?? $cleanPublicId;
            $type = $mapping?->resource_type ?? $resourceType;

            $this->cloudinary->uploadApi()->destroy($publicId, [
                'resource_type' => $type,
                'invalidate' => true,
            ]);

            if ($mapping) {
                $mapping->delete();
            }

            MediaUrlResolver::clearCache();
        } catch (Throwable $e) {
            Log::warning("CloudinaryStorageAdapter: delete failed for [{$logicalPath}]: " . $e->getMessage());
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }
}
