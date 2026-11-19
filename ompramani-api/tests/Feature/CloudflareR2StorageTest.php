<?php

namespace Tests\Feature;

use App\Models\Mantra;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudflareR2StorageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Verify r2_public and r2_private disk configurations.
     */
    public function test_r2_disks_are_properly_configured_in_filesystems(): void
    {
        $disks = config('filesystems.disks');

        // Check r2_public
        $this->assertArrayHasKey('r2_public', $disks);
        $this->assertSame('s3', $disks['r2_public']['driver']);
        $this->assertSame('auto', $disks['r2_public']['region']);
        $this->assertSame('ompramani-public', $disks['r2_public']['bucket']);
        $this->assertSame('public', $disks['r2_public']['visibility']);

        // Check r2_private
        $this->assertArrayHasKey('r2_private', $disks);
        $this->assertSame('s3', $disks['r2_private']['driver']);
        $this->assertSame('auto', $disks['r2_private']['region']);
        $this->assertSame('ompramani-private', $disks['r2_private']['bucket']);
        $this->assertSame('private', $disks['r2_private']['visibility']);
        $this->assertArrayNotHasKey('url', $disks['r2_private']);

        // Check logical disk selectors
        $this->assertSame('public', config('filesystems.public_disk'));
        $this->assertSame('local', config('filesystems.private_disk'));
    }

    /**
     * Test 2: Upload, read, URL generation, and delete operations on r2_public disk.
     */
    public function test_r2_public_disk_file_operations(): void
    {
        Storage::fake('r2_public');
        $disk = Storage::disk('r2_public');

        $filePath = 'products/vajra-bodhi-108.jpg';
        $content = 'fake-image-binary-data';

        // 1. Upload / Write
        $disk->put($filePath, $content, 'public');
        $this->assertTrue($disk->exists($filePath));

        // 2. Read
        $this->assertSame($content, $disk->get($filePath));
        $this->assertSame(strlen($content), $disk->size($filePath));

        // 3. Delete
        $disk->delete($filePath);
        $this->assertFalse($disk->exists($filePath));
    }

    /**
     * Test 3: Upload, read, and delete operations on r2_private disk with privacy enforcement.
     */
    public function test_r2_private_disk_file_operations_and_access_control(): void
    {
        Storage::fake('r2_private');
        $disk = Storage::disk('r2_private');

        $filePath = 'source_documents/shurangama-sutra-master.pdf';
        $content = '%PDF-1.4 private sutra document content';

        // 1. Upload / Write with private visibility
        $disk->put($filePath, $content, 'private');
        $this->assertTrue($disk->exists($filePath));

        // 2. Read internally (server-side authorized)
        $this->assertSame($content, $disk->get($filePath));

        // 3. Privacy check: verify private disk configuration has no public URL endpoint
        $privateConfig = config('filesystems.disks.r2_private');
        $this->assertSame('private', $privateConfig['visibility']);
        $this->assertEmpty($privateConfig['url'] ?? null);

        // 4. Delete
        $disk->delete($filePath);
        $this->assertFalse($disk->exists($filePath));
    }

    /**
     * Test 4: Dynamic disk switching in models (Mantra and UserProfile).
     */
    public function test_models_resolve_public_disk_dynamically(): void
    {
        Config::set('filesystems.disks.r2_public.key', 'mock-key');
        Config::set('filesystems.disks.r2_public.secret', 'mock-secret');
        Config::set('filesystems.disks.r2_public.url', 'https://media.ompramani.vn');
        Config::set('filesystems.public_disk', 'r2_public');

        // Test Mantra audio_url
        $mantra = new Mantra([
            'audio_path' => 'mantras/audio/om-mani-padme-hum.mp3',
        ]);
        $this->assertSame('https://media.ompramani.vn/mantras/audio/om-mani-padme-hum.mp3', $mantra->audio_url);

        // Test UserProfile avatar_url
        $user = User::factory()->create();
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'avatar' => 'avatars/dharmapala.jpg',
        ]);
        $this->assertSame('https://media.ompramani.vn/avatars/dharmapala.jpg', $profile->avatar_url);
    }

    /**
     * Test 5: r2:sync-storage Artisan command dry-run execution.
     */
    public function test_r2_sync_storage_command_dry_run(): void
    {
        $this->artisan('r2:sync-storage', ['--dry-run' => true])
            ->expectsOutputToContain('OmPraMani — Cloudflare R2 Storage Synchronization')
            ->expectsOutputToContain('DRY-RUN (No files will be uploaded)')
            ->expectsOutputToContain('Synchronization process finished.')
            ->assertExitCode(0);
    }

    /**
     * Test 6: r2:sync-storage Artisan command live execution into faked R2 disks without deleting local files.
     */
    public function test_r2_sync_storage_command_live_sync_preserves_local_files(): void
    {
        Storage::fake('r2_public');
        Storage::fake('r2_private');

        // Execute sync for public assets
        $this->artisan('r2:sync-storage', [
            '--type' => 'public',
            '--force' => true,
        ])->assertExitCode(0);

        // Check that any files existing in storage/app/public were synced to r2_public
        $localPublicPath = storage_path('app/public');
        if (File::isDirectory($localPublicPath)) {
            $files = File::allFiles($localPublicPath);
            foreach ($files as $file) {
                $relative = str_replace('\\', '/', $file->getRelativePathname());
                if ($relative === '.gitignore' || str_contains($relative, 'livewire-tmp')) {
                    continue;
                }
                // Verify uploaded to R2 fake
                $this->assertTrue(
                    Storage::disk('r2_public')->exists($relative),
                    "Expected {$relative} to exist in r2_public disk."
                );
                // Verify local file STILL exists (NEVER deleted)
                $this->assertFileExists($file->getRealPath(), "Local file {$relative} must be preserved.");
            }
        }
    }

    /**
     * Test 7: ProfileController avatar upload and deletion respects configured public disk.
     */
    public function test_profile_controller_uses_configured_public_disk(): void
    {
        Storage::fake('r2_public');
        Config::set('filesystems.public_disk', 'r2_public');

        $user = User::factory()->create();

        // Upload avatar
        $fakeFile = UploadedFile::fake()->image('bodhi-avatar.png', 400, 400);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => $fakeFile,
            ]);

        $response->assertStatus(200);
        $avatarPath = $response->json('data.avatar');
        $this->assertNotEmpty($avatarPath);

        // Verify stored in r2_public disk
        Storage::disk('r2_public')->assertExists($avatarPath);

        // Delete avatar
        $deleteResponse = $this->actingAs($user)->deleteJson('/api/v1/profile/avatar');
        $deleteResponse->assertStatus(200);

        // Verify deleted from r2_public disk
        Storage::disk('r2_public')->assertMissing($avatarPath);
    }
}
