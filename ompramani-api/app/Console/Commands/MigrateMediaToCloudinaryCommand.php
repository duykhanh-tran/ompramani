<?php

declare(strict_types=1);

namespace App\Console\Commands;

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
use App\Services\Media\MediaStorageService;
use App\Services\Media\MediaUrlResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class MigrateMediaToCloudinaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudinary:migrate-media
                            {--dry-run : Preview files that would be migrated without uploading or touching the database}
                            {--limit= : Limit the number of files to process}
                            {--type=all : Media type to process: all, images, or audio}
                            {--force : Overwrite files that have already been synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely migrate local public media (images & audio) to Cloudinary preserving folder structure and mapping';

    /**
     * Paths/patterns to exclude from migration.
     */
    protected array $excludedPatterns = [
        '.gitignore',
        '.DS_Store',
        'livewire-tmp',
        '.gitkeep',
    ];

    public function handle(MediaStorageService $storageService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $type = (string) ($this->option('type') ?: 'all');
        $limitOption = $this->option('limit');
        $limit = $limitOption !== null ? (int) $limitOption : null;

        if (! in_array($type, ['all', 'images', 'audio'], true)) {
            $this->error("Invalid --type argument: [{$type}]. Allowed values are: all, images, audio.");

            return self::FAILURE;
        }

        $this->info('====================================================');
        $this->info(' OmPraMani — Cloudinary Media Migration Engine     ');
        $this->info('====================================================');
        $this->line('Mode:     ' . ($isDryRun ? '<comment>DRY-RUN (Simulation only, no uploads, no DB changes)</comment>' : '<info>LIVE MIGRATION</info>'));
        $this->line("Type:     <comment>{$type}</comment>");
        $this->line('Force:    <comment>' . ($force ? 'YES' : 'NO') . '</comment>');
        $this->line('Limit:    <comment>' . ($limit ?: 'NONE') . '</comment>');
        $this->newLine();

        if (! $isDryRun && ! $storageService->isConfigured()) {
            $this->error('Error: Cloudinary credentials are not configured in environment or config.');
            $this->line('Please set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET in your .env or Render dashboard.');

            return self::FAILURE;
        }

        // 1. Gather all local files
        $localFiles = $this->collectLocalFiles();

        // 2. Gather all database media paths
        $dbPaths = $this->collectPathsFromDatabase();

        // Combine into unique candidate list
        $allCandidates = array_values(array_unique(array_merge($localFiles, $dbPaths)));

        $this->info(sprintf('Discovery Stats:'));
        $this->line(sprintf('  - Database media paths: %d', count($dbPaths)));
        $this->line(sprintf('  - Local storage files:  %d', count($localFiles)));
        $this->line(sprintf('  - Total unique items:   %d', count($allCandidates)));
        $this->newLine();

        // Filter candidates by type (images / audio / all)
        $filtered = array_filter($allCandidates, function (string $path) use ($type) {
            if ($type === 'images') {
                return MediaUrlResolver::isImage($path);
            }
            if ($type === 'audio') {
                return MediaUrlResolver::isAudio($path);
            }

            return MediaUrlResolver::isImage($path) || MediaUrlResolver::isAudio($path);
        });

        if ($limit && $limit > 0) {
            $filtered = array_slice($filtered, 0, $limit);
        }

        $this->info(sprintf('Processing %d media items under filter [%s]...', count($filtered), $type));
        $this->newLine();

        $stats = [
            'total_candidates' => count($filtered),
            'uploaded' => 0,
            'skipped' => 0,
            'missing' => 0,
            'failed' => 0,
        ];

        $manifestEntries = [];
        $publicDir = storage_path('app/public');

        foreach ($filtered as $relativePath) {
            $fullLocalPath = $publicDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
            $resourceType = MediaUrlResolver::getResourceType($relativePath);

            // Check if local file exists
            if (! file_exists($fullLocalPath) || ! is_file($fullLocalPath)) {
                $this->warn("  [MISSING] Local file not found: {$relativePath}");
                $stats['missing']++;
                $manifestEntries[] = [
                    'path' => $relativePath,
                    'status' => 'missing',
                    'resource_type' => $resourceType,
                    'error' => 'File does not exist in storage/app/public',
                ];

                continue;
            }

            $checksum = (string) hash_file('sha256', $fullLocalPath);
            $fileSize = (int) filesize($fullLocalPath);

            // Check if already synced
            $existingMapping = null;
            try {
                $existingMapping = CloudinaryMediaMapping::query()
                    ->where('local_path', $relativePath)
                    ->first();
            } catch (Throwable) {
                // Table might not exist in dry run / fresh db
            }

            if (! $force && $existingMapping && $existingMapping->status === 'synced' && $existingMapping->checksum === $checksum) {
                $this->line("  [SKIP] Already synced: {$relativePath}");
                $stats['skipped']++;
                $manifestEntries[] = [
                    'path' => $relativePath,
                    'status' => 'skipped',
                    'public_id' => $existingMapping->public_id,
                    'secure_url' => $existingMapping->secure_url,
                    'resource_type' => $existingMapping->resource_type,
                ];

                continue;
            }

            if ($isDryRun) {
                $this->line("  [DRY-RUN] Would upload: {$relativePath} ({$resourceType}, " . round($fileSize / 1024, 1) . ' KB)');
                $stats['uploaded']++;
                $manifestEntries[] = [
                    'path' => $relativePath,
                    'status' => 'dry-run-preview',
                    'resource_type' => $resourceType,
                    'file_size' => $fileSize,
                    'checksum' => $checksum,
                    'target_url' => MediaUrlResolver::buildCloudinaryUrl($relativePath),
                ];

                continue;
            }

            try {
                $this->line("  [UPLOADING] {$relativePath} ({$resourceType}, " . round($fileSize / 1024, 1) . ' KB)...');
                $result = $storageService->uploadToCloudinary($fullLocalPath, $relativePath, [
                    'overwrite' => $force,
                ]);

                $stats['uploaded']++;
                $this->info("    -> OK: {$result['secure_url']}");

                $manifestEntries[] = [
                    'path' => $relativePath,
                    'status' => 'synced',
                    'public_id' => $result['public_id'],
                    'secure_url' => $result['secure_url'],
                    'resource_type' => $result['resource_type'],
                    'checksum' => $checksum,
                    'file_size' => $fileSize,
                ];
            } catch (Throwable $e) {
                $stats['failed']++;
                $this->error("    -> FAILED: {$relativePath} - {$e->getMessage()}");

                $manifestEntries[] = [
                    'path' => $relativePath,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->newLine();
        $this->info('====================================================');
        $this->info(' Migration Summary Report                          ');
        $this->info('====================================================');
        $this->line(sprintf('Tổng số đường dẫn media trong DB:  %d', count($dbPaths)));
        $this->line(sprintf('Tổng số file local thực tế:        %d', count($localFiles)));
        $this->line(sprintf('Số file xử lý / upload:            %s', $stats['uploaded']));
        $this->line(sprintf('Số file bỏ qua (đã sync):          %s', $stats['skipped']));
        $this->line(sprintf('Số file thiếu (missing local):     %s', $stats['missing']));
        $this->line(sprintf('Số file thất bại:                  %s', $stats['failed']));

        $totalSyncedInDb = 0;
        try {
            $totalSyncedInDb = CloudinaryMediaMapping::query()->where('status', 'synced')->count();
        } catch (Throwable) {
            // Ignore
        }
        $this->line(sprintf('Số mapping thành công trong DB:    %d', $totalSyncedInDb));
        $this->newLine();

        // Write Manifest file for auditing and rollback
        $manifestPath = storage_path('app/cloudinary_migration_manifest.json');
        File::ensureDirectoryExists(dirname($manifestPath));
        File::put($manifestPath, json_encode([
            'timestamp' => now()->toIso8601String(),
            'dry_run' => $isDryRun,
            'type' => $type,
            'db_paths_count' => count($dbPaths),
            'local_files_count' => count($localFiles),
            'stats' => $stats,
            'total_synced_in_db' => $totalSyncedInDb,
            'entries' => $manifestEntries,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->info("Manifest written to: [{$manifestPath}]");

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Collect local files from storage/app/public.
     *
     * @return array<string>
     */
    protected function collectLocalFiles(): array
    {
        $paths = [];
        $publicDir = storage_path('app/public');

        if (File::isDirectory($publicDir)) {
            $files = File::allFiles($publicDir);
            foreach ($files as $file) {
                if ($file instanceof SplFileInfo) {
                    $relative = str_replace('\\', '/', $file->getRelativePathname());
                    if ($this->shouldIncludePath($relative)) {
                        $paths[] = $relative;
                    }
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Collect media paths directly stored in database tables.
     *
     * @return array<string>
     */
    protected function collectPathsFromDatabase(): array
    {
        $dbPaths = [];

        try {
            // Products
            $products = Product::query()->whereNotNull('cover_image')->pluck('cover_image')->all();
            $dbPaths = array_merge($dbPaths, $products);

            // Articles
            $articles = Article::query()->whereNotNull('cover_image')->pluck('cover_image')->all();
            $dbPaths = array_merge($dbPaths, $articles);

            // Figures
            $figures = BuddhistFigure::query()->whereNotNull('thumbnail')->pluck('thumbnail')->all();
            $dbPaths = array_merge($dbPaths, $figures);

            // Sutras
            $sutras = Sutra::query()->whereNotNull('thumbnail')->pluck('thumbnail')->all();
            $dbPaths = array_merge($dbPaths, $sutras);

            // Temples & Temple Media
            $temples = Temple::query()->whereNotNull('cover_image')->pluck('cover_image')->all();
            $templeMedia = TempleMedia::query()->whereNotNull('file_path')->pluck('file_path')->all();
            $dbPaths = array_merge($dbPaths, $temples, $templeMedia);

            // Mantras & Recitation Audios
            $mantras = Mantra::query()->whereNotNull('audio_path')->pluck('audio_path')->all();
            $audios = RecitationAudio::query()->whereNotNull('file_path')->pluck('file_path')->all();
            $dbPaths = array_merge($dbPaths, $mantras, $audios);

            // Avatars
            $avatars = UserProfile::query()->whereNotNull('avatar')->pluck('avatar')->all();
            $dbPaths = array_merge($dbPaths, $avatars);
        } catch (Throwable $e) {
            $this->warn('Could not read all database tables: ' . $e->getMessage());
        }

        $cleaned = [];
        foreach ($dbPaths as $path) {
            if (! is_string($path) || blank($path)) {
                continue;
            }
            if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
                continue;
            }
            $clean = ltrim(trim($path), '/');
            if (Str::startsWith($clean, 'storage/')) {
                $clean = substr($clean, 8);
            }
            if ($this->shouldIncludePath($clean)) {
                $cleaned[] = $clean;
            }
        }

        return array_values(array_unique($cleaned));
    }

    /**
     * Determine if a relative path should be included.
     */
    protected function shouldIncludePath(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        foreach ($this->excludedPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return false;
            }
        }

        return true;
    }
}
