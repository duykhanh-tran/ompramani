<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SyncStorageToR2Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'r2:sync-storage
                            {--type=all : Sync target: all, public, or private}
                            {--dry-run : Preview files that would be synced without uploading}
                            {--force : Overwrite files that already exist in R2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely sync local storage files to Cloudflare R2 buckets preserving relative paths';

    /**
     * Paths/patterns to exclude from synchronization.
     */
    protected array $excludedPatterns = [
        '.gitignore',
        '.DS_Store',
        'livewire-tmp',
        '.gitkeep',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = (string) $this->option('type');
        $isDryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! in_array($type, ['all', 'public', 'private'], true)) {
            $this->error("Invalid --type argument: [{$type}]. Allowed values are: all, public, private.");

            return self::FAILURE;
        }

        $this->info('====================================================');
        $this->info(' OmPraMani — Cloudflare R2 Storage Synchronization ');
        $this->info('====================================================');
        $this->line("Mode:     " . ($isDryRun ? '<comment>DRY-RUN (No files will be uploaded)</comment>' : '<info>LIVE SYNC</info>'));
        $this->line("Target:   <comment>{$type}</comment>");
        $this->line("Overwrite:<comment>" . ($force ? 'YES' : 'NO') . '</comment>');
        $this->newLine();

        $overallExitCode = self::SUCCESS;

        if ($type === 'all' || $type === 'public') {
            $exit = $this->syncDirectory(
                localPath: storage_path('app/public'),
                targetDisk: 'r2_public',
                targetBucketLabel: 'ompramani-public',
                isPublic: true,
                isDryRun: $isDryRun,
                force: $force
            );
            if ($exit !== self::SUCCESS) {
                $overallExitCode = $exit;
            }
        }

        if ($type === 'all' || $type === 'private') {
            $exit = $this->syncDirectory(
                localPath: storage_path('app/private'),
                targetDisk: 'r2_private',
                targetBucketLabel: 'ompramani-private',
                isPublic: false,
                isDryRun: $isDryRun,
                force: $force
            );
            if ($exit !== self::SUCCESS) {
                $overallExitCode = $exit;
            }
        }

        $this->newLine();
        $this->info('Synchronization process finished.');

        return $overallExitCode;
    }

    /**
     * Sync a single local directory to a target R2 disk.
     */
    protected function syncDirectory(
        string $localPath,
        string $targetDisk,
        string $targetBucketLabel,
        bool $isPublic,
        bool $isDryRun,
        bool $force
    ): int {
        $this->info("Scanning local directory: [{$localPath}]");
        $this->info("Target R2 disk:          [{$targetDisk}] -> Bucket: [{$targetBucketLabel}] (Visibility: " . ($isPublic ? 'public' : 'private') . ')');

        if (! File::isDirectory($localPath)) {
            $this->warn("Local directory does not exist: [{$localPath}]. Skipping.");

            return self::SUCCESS;
        }

        $allFiles = File::allFiles($localPath);
        $eligibleFiles = [];

        foreach ($allFiles as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());

            if ($this->isExcluded($relativePath)) {
                continue;
            }

            $eligibleFiles[] = [
                'absolute_path' => $file->getRealPath(),
                'relative_path' => $relativePath,
                'size' => $file->getSize(),
            ];
        }

        $count = count($eligibleFiles);
        $this->line("Found <info>{$count}</info> eligible files to sync.");

        if ($count === 0) {
            return self::SUCCESS;
        }

        $uploaded = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        foreach ($eligibleFiles as $item) {
            $rel = $item['relative_path'];
            $abs = $item['absolute_path'];
            $size = $item['size'];

            if ($isDryRun) {
                $this->output->write(" [DRY-RUN] Would sync: {$rel} (" . number_format($size / 1024, 1) . ' KB)' . PHP_EOL);
                $uploaded++;
                $progressBar->advance();
                continue;
            }

            try {
                $r2 = Storage::disk($targetDisk);

                if (! $force && $r2->exists($rel)) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $stream = fopen($abs, 'rb');
                if ($stream === false) {
                    $this->output->write(PHP_EOL . "<error>Failed to open stream for [{$abs}]</error>" . PHP_EOL);
                    $errors++;
                    $progressBar->advance();
                    continue;
                }

                $options = $isPublic ? ['visibility' => 'public'] : ['visibility' => 'private'];
                $success = $r2->put($rel, $stream, $options);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                if ($success) {
                    $uploaded++;
                } else {
                    $this->output->write(PHP_EOL . "<error>R2 put returned false for [{$rel}]</error>" . PHP_EOL);
                    $errors++;
                }
            } catch (Throwable $e) {
                $this->output->write(PHP_EOL . "<error>Error uploading [{$rel}]: {$e->getMessage()}</error>" . PHP_EOL);
                $errors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->line("Summary for [{$targetDisk}]:");
        $this->line(" - Uploaded / Planned: <info>{$uploaded}</info>");
        $this->line(" - Skipped (already in R2): <comment>{$skipped}</comment>");
        $this->line(" - Errors:             " . ($errors > 0 ? "<error>{$errors}</error>" : '<info>0</info>'));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Check if a relative path matches any exclusion pattern.
     */
    protected function isExcluded(string $relativePath): bool
    {
        foreach ($this->excludedPatterns as $pattern) {
            if ($pattern === $relativePath || str_contains($relativePath, '/' . $pattern) || str_starts_with($relativePath, $pattern . '/')) {
                return true;
            }
        }

        return false;
    }
}
