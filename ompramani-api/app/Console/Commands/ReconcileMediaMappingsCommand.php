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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class ReconcileMediaMappingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudinary:reconcile-media
                            {--dry-run : Inspect and report media inconsistencies without writing to DB or uploading}
                            {--fix : Automatically repair confirmed issues (upload missing files, fix double extensions, fix sutra 7 mismatch)}
                            {--force : Force re-upload / overwrite even if mapping exists}
                            {--output-csv= : Custom path for the CSV report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit, reconcile, and safely repair Cloudinary media mappings and local assets';

    public function handle(MediaStorageService $storageService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $shouldFix = (bool) $this->option('fix');
        $force = (bool) $this->option('force');
        $csvOption = $this->option('output-csv');

        // If neither --dry-run nor --fix is passed, default to safe dry-run mode
        if (! $isDryRun && ! $shouldFix) {
            $isDryRun = true;
            $this->warn('Notice: Neither --fix nor --dry-run specified. Running in safe DRY-RUN preview mode.');
        }

        $this->info('===============================================================');
        $this->info(' OmPraMani — Cloudinary Media Audit & Reconciliation Engine    ');
        $this->info('===============================================================');
        $this->line('Mode:     ' . ($shouldFix ? '<fg=green;options=bold>AUTO-REPAIR (--fix)</>' : '<fg=yellow;options=bold>AUDIT ONLY (DRY-RUN)</>'));
        $this->line('Force:    ' . ($force ? '<comment>YES</comment>' : 'NO'));
        $this->newLine();

        if (! Schema::hasTable('cloudinary_media_mappings')) {
            $this->error('Lỗi: Bảng [cloudinary_media_mappings] chưa tồn tại trên cơ sở dữ liệu hiện tại.');
            $this->line('Vui lòng chạy: php artisan migrate --force');

            return self::FAILURE;
        }

        $publicDir = storage_path('app/public');
        $reportEntries = [];
        $stats = [
            'total_inspected' => 0,
            'clean' => 0,
            'missing_mappings' => 0,
            'double_extensions' => 0,
            'invalid_resource_types' => 0,
            'mismatched_assets' => 0,
            'fixed' => 0,
            'failed' => 0,
        ];

        // 1. Audit Sutra ID 7 (Kinh A Di Đà) special case
        $this->auditSutra7($storageService, $shouldFix, $force, $stats, $reportEntries);

        // 2. Audit existing mappings in cloudinary_media_mappings table
        $this->auditExistingMappings($storageService, $shouldFix, $stats, $reportEntries);

        // 3. Audit all media paths across database tables
        $this->auditDatabaseMedia($storageService, $shouldFix, $force, $stats, $reportEntries);

        // 4. Audit local files in storage/app/public
        $this->auditLocalFiles($storageService, $shouldFix, $force, $stats, $reportEntries);

        // Deduplicate report entries by path
        $uniqueEntries = [];
        foreach ($reportEntries as $entry) {
            $key = ($entry['path'] ?? '') . '|' . ($entry['issue_type'] ?? '');
            if (! isset($uniqueEntries[$key])) {
                $uniqueEntries[$key] = $entry;
            }
        }
        $reportEntries = array_values($uniqueEntries);

        // Summary report
        $this->newLine();
        $this->info('===============================================================');
        $this->info(' Reconciliation Summary Report                                 ');
        $this->info('===============================================================');
        $this->line(sprintf('Tổng số hạng mục đã kiểm tra:      %d', $stats['total_inspected']));
        $this->line(sprintf('Hợp lệ, chuẩn xác:                 %d', $stats['clean']));
        $this->line(sprintf('Thiếu mapping trên database:       %d', $stats['missing_mappings']));
        $this->line(sprintf('Lỗi đuôi lặp (.jpg.jpg, ...):      %d', $stats['double_extensions']));
        $this->line(sprintf('Sai resource_type (audio/image):   %d', $stats['invalid_resource_types']));
        $this->line(sprintf('Sai asset / trỏ nhầm hình ảnh:     %d', $stats['mismatched_assets']));
        if ($shouldFix) {
            $this->line(sprintf('Đã khắc phục thành công:           <fg=green;options=bold>%d</>', $stats['fixed']));
            $this->line(sprintf('Khắc phục thất bại / lỗi:          <fg=red;options=bold>%d</>', $stats['failed']));
        }
        $this->newLine();

        // Write CSV report
        $csvPath = $csvOption ?: storage_path('app/cloudinary_reconciliation_report.csv');
        $this->exportCsvReport($csvPath, $reportEntries);
        $this->info("Báo cáo chi tiết CSV đã ghi tại: [{$csvPath}]");

        // Write JSON manifest
        $manifestPath = storage_path('app/cloudinary_reconciliation_manifest.json');
        File::ensureDirectoryExists(dirname($manifestPath));
        File::put($manifestPath, json_encode([
            'generated_at' => now()->toIso8601String(),
            'mode' => $shouldFix ? 'fix' : 'dry-run',
            'stats' => $stats,
            'entries' => $reportEntries,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->info("Manifest JSON đã ghi tại:       [{$manifestPath}]");

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Audit Sutra 7 (Kinh A Di Đà) thumbnail to prevent pointing to buddhist-figures asset.
     */
    protected function auditSutra7(
        MediaStorageService $storageService,
        bool $shouldFix,
        bool $force,
        array &$stats,
        array &$reportEntries
    ): void {
        $stats['total_inspected']++;
        $this->line('<fg=cyan>[1/4] Kiểm tra chuyên biệt bản ghi Kinh A Di Đà (sutras.id = 7)...</>');

        try {
            $sutra = Sutra::query()->find(7);
            if (! $sutra) {
                // Try finding by title
                $sutra = Sutra::query()->where('title', 'like', '%A Di Đà%')->first();
            }

            if (! $sutra) {
                $this->line('  - Không tìm thấy bản ghi sutras.id = 7 trong database hiện tại.');

                return;
            }

            $thumb = (string) $sutra->thumbnail;
            $this->line("  - Bản ghi: ID {$sutra->id} [{$sutra->title}], thumbnail: [{$thumb}]");

            $mapping = CloudinaryMediaMapping::query()
                ->where('local_path', $thumb)
                ->first();

            $isMismatched = false;
            if ($mapping) {
                // If mapping points to buddhist-figures, it was manually paired to the wrong figure asset
                if (Str::contains((string) $mapping->public_id, 'buddhist-figures') ||
                    Str::contains((string) $mapping->secure_url, 'buddhist-figures')) {
                    $isMismatched = true;
                }
            }

            if ($isMismatched) {
                $stats['mismatched_assets']++;
                $this->warn("  ! CẢNH BÁO: Thumbnail của Kinh A Di Đà đang trỏ nhầm sang asset của Phật/Bồ Tát: [{$mapping->public_id}]");

                $localFile = storage_path('app/public/' . ltrim($thumb, '/'));
                if (file_exists($localFile) && is_file($localFile)) {
                    $this->line("  - Đã tìm thấy file gốc thực tế: [{$localFile}] (" . filesize($localFile) . ' bytes)');

                    if ($shouldFix) {
                        if ($storageService->isConfigured()) {
                            $this->line('  - Đang tải ảnh gốc Kinh A Di Đà lên Cloudinary...');
                            $res = $storageService->uploadToCloudinary($localFile, $thumb, ['overwrite' => true]);
                            $this->info("  -> Đã khắc phục mapping cho Kinh A Di Đà: [{$res['secure_url']}]");
                            $stats['fixed']++;

                            $reportEntries[] = [
                                'path' => $thumb,
                                'source' => 'sutras.id=7',
                                'issue_type' => 'MISMATCHED_ASSET',
                                'status' => 'FIXED',
                                'old_url' => $mapping->secure_url,
                                'new_url' => $res['secure_url'],
                                'notes' => 'Remapped to authentic sutra thumbnail',
                            ];
                        } else {
                            $this->warn('  - Không thể upload vì chưa cấu hình Cloudinary credentials.');
                            $stats['failed']++;
                        }
                    } else {
                        $reportEntries[] = [
                            'path' => $thumb,
                            'source' => 'sutras.id=7',
                            'issue_type' => 'MISMATCHED_ASSET',
                            'status' => 'DETECTED',
                            'old_url' => $mapping->secure_url,
                            'new_url' => null,
                            'notes' => 'Thumbnail mapped to buddhist-figures asset instead of sutras/thumbnails/',
                        ];
                    }
                } else {
                    $this->warn("  - File ảnh gốc [{$localFile}] không tồn tại trên local disk.");
                    $reportEntries[] = [
                        'path' => $thumb,
                        'source' => 'sutras.id=7',
                        'issue_type' => 'MISMATCHED_ASSET_NO_LOCAL',
                        'status' => 'MANUAL_ATTENTION',
                        'old_url' => $mapping->secure_url,
                        'new_url' => null,
                        'notes' => 'Local authentic sutra file missing, manual upload required',
                    ];
                }
            } else {
                $stats['clean']++;
                $this->line('  - Mapping của Kinh A Di Đà chuẩn xác hoặc chưa phát hiện sai lệch.');
            }
        } catch (Throwable $e) {
            $this->warn('  - Lỗi khi kiểm tra Kinh A Di Đà: ' . $e->getMessage());
        }
    }

    /**
     * Audit all rows in cloudinary_media_mappings for double extensions and wrong resource_types.
     */
    protected function auditExistingMappings(
        MediaStorageService $storageService,
        bool $shouldFix,
        array &$stats,
        array &$reportEntries
    ): void {
        $this->newLine();
        $this->line('<fg=cyan>[2/4] Kiểm tra các bản ghi trong bảng cloudinary_media_mappings...</>');

        try {
            $mappings = CloudinaryMediaMapping::all();
            $this->line(sprintf('  - Tìm thấy %d bản ghi mapping.', $mappings->count()));

            foreach ($mappings as $mapping) {
                $stats['total_inspected']++;
                $path = (string) $mapping->local_path;
                $url = (string) $mapping->secure_url;
                $publicId = (string) $mapping->public_id;
                $resourceType = (string) $mapping->resource_type;
                $expectedType = MediaUrlResolver::getResourceType($path);

                $hasDoubleExt = false;
                $cleanedUrl = MediaUrlResolver::cleanDoubleExtension($url);
                $cleanedPublicId = MediaUrlResolver::extractPublicId($publicId);
                if ($cleanedUrl !== $url || $cleanedPublicId !== $publicId) {
                    $hasDoubleExt = true;
                    $stats['double_extensions']++;
                }

                $hasWrongType = false;
                if ($expectedType !== $resourceType) {
                    $hasWrongType = true;
                    $stats['invalid_resource_types']++;
                }

                if ($hasDoubleExt || $hasWrongType) {
                    $this->warn("  ! Bất thường tại [{$path}]: " .
                        ($hasDoubleExt ? "[Đuôi lặp .jpg.jpg] " : '') .
                        ($hasWrongType ? "[Sai resource_type: hiện tại={$resourceType}, đúng={$expectedType}]" : ''));

                    if ($shouldFix) {
                        $updateData = [];
                        if ($hasDoubleExt) {
                            $updateData['secure_url'] = $cleanedUrl;
                            $updateData['public_id'] = $cleanedPublicId;
                        }
                        if ($hasWrongType) {
                            $updateData['resource_type'] = $expectedType;
                            // Also adjust URL endpoint if video vs image
                            if ($expectedType === 'video' && Str::contains($updateData['secure_url'] ?? $url, '/image/upload/')) {
                                $updateData['secure_url'] = str_replace('/image/upload/', '/video/upload/', $updateData['secure_url'] ?? $url);
                            }
                        }

                        $mapping->update($updateData);
                        $stats['fixed']++;
                        $this->info("    -> Đã cập nhật mapping cho [{$path}]");

                        $reportEntries[] = [
                            'path' => $path,
                            'source' => 'cloudinary_media_mappings',
                            'issue_type' => ($hasDoubleExt ? 'DOUBLE_EXT ' : '') . ($hasWrongType ? 'WRONG_TYPE' : ''),
                            'status' => 'FIXED',
                            'old_url' => $url,
                            'new_url' => $mapping->secure_url,
                            'notes' => 'Sanitized double extension or resource_type',
                        ];
                    } else {
                        $reportEntries[] = [
                            'path' => $path,
                            'source' => 'cloudinary_media_mappings',
                            'issue_type' => ($hasDoubleExt ? 'DOUBLE_EXT ' : '') . ($hasWrongType ? 'WRONG_TYPE' : ''),
                            'status' => 'DETECTED',
                            'old_url' => $url,
                            'new_url' => $cleanedUrl,
                            'notes' => 'Requires double extension cleanup or resource_type fix',
                        ];
                    }
                } else {
                    $stats['clean']++;
                }
            }
        } catch (Throwable $e) {
            $this->warn('  - Lỗi khi kiểm tra cloudinary_media_mappings: ' . $e->getMessage());
        }
    }

    /**
     * Audit media paths across database tables to find records with missing mappings.
     */
    protected function auditDatabaseMedia(
        MediaStorageService $storageService,
        bool $shouldFix,
        bool $force,
        array &$stats,
        array &$reportEntries
    ): void {
        $this->newLine();
        $this->line('<fg=cyan>[3/4] Đối soát các trường media trong toàn bộ cơ sở dữ liệu...</>');

        $tablesToCheck = [
            ['model' => Article::class, 'column' => 'cover_image', 'name' => 'articles.cover_image'],
            ['model' => Product::class, 'column' => 'cover_image', 'name' => 'products.cover_image'],
            ['model' => BuddhistFigure::class, 'column' => 'thumbnail', 'name' => 'buddhist_figures.thumbnail'],
            ['model' => Sutra::class, 'column' => 'thumbnail', 'name' => 'sutras.thumbnail'],
            ['model' => Temple::class, 'column' => 'cover_image', 'name' => 'temples.cover_image'],
            ['model' => TempleMedia::class, 'column' => 'file_path', 'name' => 'temple_media.file_path'],
            ['model' => Mantra::class, 'column' => 'audio_path', 'name' => 'mantras.audio_path'],
            ['model' => RecitationAudio::class, 'column' => 'file_path', 'name' => 'recitation_audios.file_path'],
            ['model' => UserProfile::class, 'column' => 'avatar', 'name' => 'user_profiles.avatar'],
        ];

        foreach ($tablesToCheck as $check) {
            $modelClass = $check['model'];
            $column = $check['column'];
            $name = $check['name'];

            try {
                $paths = $modelClass::query()->whereNotNull($column)->pluck($column)->all();
                foreach ($paths as $rawPath) {
                    if (! is_string($rawPath) || blank($rawPath)) {
                        continue;
                    }
                    if (Str::startsWith($rawPath, ['http://', 'https://', 'data:'])) {
                        continue;
                    }

                    $stats['total_inspected']++;
                    $cleanPath = ltrim(trim($rawPath), '/');
                    if (Str::startsWith($cleanPath, 'storage/')) {
                        $cleanPath = substr($cleanPath, 8);
                    }
                    $cleanPath = MediaUrlResolver::cleanDoubleExtension($cleanPath);

                    $mapping = CloudinaryMediaMapping::query()
                        ->where(function ($q) use ($cleanPath) {
                            $q->where('local_path', $cleanPath)
                              ->orWhere('local_path', 'storage/' . $cleanPath);
                        })
                        ->first();

                    if (! $mapping || $force) {
                        $stats['missing_mappings']++;
                        $this->warn("  ! Thiếu mapping cho [{$name}]: {$cleanPath}");

                        $localFile = storage_path('app/public/' . $cleanPath);
                        if (file_exists($localFile) && is_file($localFile)) {
                            if ($shouldFix) {
                                if ($storageService->isConfigured()) {
                                    $this->line("    -> Đang đồng bộ file local lên Cloudinary: {$cleanPath}");
                                    $res = $storageService->uploadToCloudinary($localFile, $cleanPath, ['overwrite' => $force]);
                                    $this->info("    -> OK: {$res['secure_url']}");
                                    $stats['fixed']++;

                                    $reportEntries[] = [
                                        'path' => $cleanPath,
                                        'source' => $name,
                                        'issue_type' => 'MISSING_MAPPING',
                                        'status' => 'UPLOADED_AND_MAPPED',
                                        'old_url' => null,
                                        'new_url' => $res['secure_url'],
                                        'notes' => 'Uploaded local file and created mapping',
                                    ];
                                } else {
                                    $stats['failed']++;
                                }
                            } else {
                                $reportEntries[] = [
                                    'path' => $cleanPath,
                                    'source' => $name,
                                    'issue_type' => 'MISSING_MAPPING',
                                    'status' => 'LOCAL_EXISTS_UNMAPPED',
                                    'old_url' => null,
                                    'new_url' => null,
                                    'notes' => 'Local file exists; needs upload and mapping',
                                ];
                            }
                        } else {
                            // Check if asset already exists on Cloudinary by public ID
                            $existsOnCloud = $storageService->existsInCloudinary($cleanPath);
                            if ($existsOnCloud) {
                                $cloudUrl = MediaUrlResolver::buildCloudinaryUrl($cleanPath);
                                if ($shouldFix) {
                                    CloudinaryMediaMapping::updateOrCreate(
                                        ['local_path' => $cleanPath],
                                        [
                                            'public_id' => MediaUrlResolver::extractPublicId($cleanPath),
                                            'resource_type' => MediaUrlResolver::getResourceType($cleanPath),
                                            'format' => pathinfo($cleanPath, PATHINFO_EXTENSION),
                                            'secure_url' => $cloudUrl,
                                            'status' => 'synced',
                                        ]
                                    );
                                    $stats['fixed']++;
                                    $this->info("    -> Đã tạo mapping từ asset sẵn có trên Cloudinary: {$cloudUrl}");

                                    $reportEntries[] = [
                                        'path' => $cleanPath,
                                        'source' => $name,
                                        'issue_type' => 'MISSING_MAPPING_CLOUD_FOUND',
                                        'status' => 'MAPPED_FROM_CLOUD',
                                        'old_url' => null,
                                        'new_url' => $cloudUrl,
                                        'notes' => 'Asset already on Cloudinary; mapping restored',
                                    ];
                                } else {
                                    $reportEntries[] = [
                                        'path' => $cleanPath,
                                        'source' => $name,
                                        'issue_type' => 'MISSING_MAPPING_CLOUD_FOUND',
                                        'status' => 'CAN_BE_MAPPED_FROM_CLOUD',
                                        'old_url' => null,
                                        'new_url' => $cloudUrl,
                                        'notes' => 'Asset verified on Cloudinary, ready for mapping creation',
                                    ];
                                }
                            } else {
                                $this->line("    - File không tồn tại cả ở local lẫn Cloudinary: {$cleanPath}");
                                $reportEntries[] = [
                                    'path' => $cleanPath,
                                    'source' => $name,
                                    'issue_type' => 'ORPHAN_UNAVAILABLE',
                                    'status' => 'UNRESOLVED',
                                    'old_url' => null,
                                    'new_url' => null,
                                    'notes' => 'File missing from both local disk and Cloudinary',
                                ];
                            }
                        }
                    } else {
                        $stats['clean']++;
                    }
                }
            } catch (Throwable $e) {
                $this->warn("  - Lỗi khi kiểm tra {$name}: " . $e->getMessage());
            }
        }
    }

    /**
     * Audit local files in storage/app/public to ensure they have mappings.
     */
    protected function auditLocalFiles(
        MediaStorageService $storageService,
        bool $shouldFix,
        bool $force,
        array &$stats,
        array &$reportEntries
    ): void {
        $this->newLine();
        $this->line('<fg=cyan>[4/4] Quét các file hiện diện trong storage/app/public...</>');

        $publicDir = storage_path('app/public');
        if (! File::isDirectory($publicDir)) {
            $this->line('  - Thư mục storage/app/public không tồn tại.');

            return;
        }

        $files = File::allFiles($publicDir);
        $this->line(sprintf('  - Tìm thấy %d file trong storage/app/public.', count($files)));

        foreach ($files as $file) {
            if (! ($file instanceof SplFileInfo)) {
                continue;
            }

            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            if (Str::startsWith($relativePath, ['.', 'livewire-tmp/'])) {
                continue;
            }

            $stats['total_inspected']++;
            $cleanPath = MediaUrlResolver::cleanDoubleExtension($relativePath);

            $mapping = null;
            try {
                $mapping = CloudinaryMediaMapping::query()
                    ->where('local_path', $cleanPath)
                    ->first();
            } catch (Throwable) {
                // Ignore DB error
            }

            if (! $mapping || $force) {
                $this->line("  - File local chưa có mapping: {$cleanPath}");

                if ($shouldFix) {
                    if ($storageService->isConfigured()) {
                        try {
                            $res = $storageService->uploadToCloudinary($file->getRealPath(), $cleanPath, ['overwrite' => $force]);
                            $this->info("    -> Đã tải lên và tạo mapping: {$res['secure_url']}");
                            $stats['fixed']++;

                            $reportEntries[] = [
                                'path' => $cleanPath,
                                'source' => 'storage/app/public',
                                'issue_type' => 'UNMAPPED_LOCAL_FILE',
                                'status' => 'UPLOADED_AND_MAPPED',
                                'old_url' => null,
                                'new_url' => $res['secure_url'],
                                'notes' => 'Local file uploaded and mapping recorded',
                            ];
                        } catch (Throwable $e) {
                            $stats['failed']++;
                            $this->error("    -> Upload thất bại: {$e->getMessage()}");
                        }
                    }
                } else {
                    $reportEntries[] = [
                        'path' => $cleanPath,
                        'source' => 'storage/app/public',
                        'issue_type' => 'UNMAPPED_LOCAL_FILE',
                        'status' => 'NEEDS_UPLOAD',
                        'old_url' => null,
                        'new_url' => null,
                        'notes' => 'Local file present without mapping',
                    ];
                }
            } else {
                $stats['clean']++;
            }
        }
    }

    /**
     * Export manifest entries to a CSV file.
     *
     * @param array<array<string, mixed>> $entries
     */
    protected function exportCsvReport(string $filePath, array $entries): void
    {
        File::ensureDirectoryExists(dirname($filePath));
        $handle = fopen($filePath, 'w');
        if (! $handle) {
            return;
        }

        // CSV Header
        fputcsv($handle, [
            'path',
            'source',
            'issue_type',
            'status',
            'old_url',
            'new_url',
            'notes',
        ]);

        foreach ($entries as $entry) {
            fputcsv($handle, [
                $entry['path'] ?? '',
                $entry['source'] ?? '',
                $entry['issue_type'] ?? '',
                $entry['status'] ?? '',
                $entry['old_url'] ?? '',
                $entry['new_url'] ?? '',
                $entry['notes'] ?? '',
            ]);
        }

        fclose($handle);
    }
}
