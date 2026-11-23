<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Articles\Tables\ArticlesTable;
use App\Filament\Resources\BuddhistFigures\Tables\BuddhistFiguresTable;
use App\Filament\Resources\Mantras\Tables\MantrasTable;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Resources\Sutras\Tables\SutrasTable;
use App\Filament\Resources\Temples\Tables\TemplesTable;
use App\Http\Resources\Api\V1\ArticleListResource;
use App\Http\Resources\Api\V1\BuddhistFigureResource;
use App\Http\Resources\Api\V1\MantraResource;
use App\Http\Resources\Api\V1\ProductSummaryResource;
use App\Http\Resources\Api\V1\SutraResource;
use App\Http\Resources\Api\V1\TempleSummaryResource;
use App\Models\Article;
use App\Models\BuddhistFigure;
use App\Models\CloudinaryMediaMapping;
use App\Models\Mantra;
use App\Models\Product;
use App\Models\Sutra;
use App\Models\Temple;
use App\Services\Media\MediaStorageService;
use App\Services\Media\MediaUrlResolver;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudinaryMediaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        MediaUrlResolver::clearCache();
    }

    public function test_media_url_resolver_preserves_absolute_and_data_urls(): void
    {
        $httpsUrl = 'https://res.cloudinary.com/demo/image/upload/sample.jpg';
        $httpUrl = 'http://example.com/audio/chant.mp3';
        $dataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $this->assertSame($httpsUrl, MediaUrlResolver::resolve($httpsUrl));
        $this->assertSame($httpUrl, MediaUrlResolver::resolve($httpUrl));
        $this->assertSame($dataUri, MediaUrlResolver::resolve($dataUri));
    }

    public function test_media_url_resolver_uses_synced_mapping_from_database(): void
    {
        $localPath = 'products/01M77F68ADYVEM54G1HZD4JYWQ.jpg';
        $cloudUrl = 'https://res.cloudinary.com/ompramani/image/upload/products/01M77F68ADYVEM54G1HZD4JYWQ.jpg';

        CloudinaryMediaMapping::create([
            'local_path' => $localPath,
            'public_id' => 'products/01M77F68ADYVEM54G1HZD4JYWQ',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => $cloudUrl,
            'checksum' => hash('sha256', 'dummy-content'),
            'file_size' => 1024,
            'status' => 'synced',
        ]);

        $resolved = MediaUrlResolver::resolve($localPath);
        $this->assertSame($cloudUrl, $resolved);

        // Also handles leading slash and storage/ prefix
        $this->assertSame($cloudUrl, MediaUrlResolver::resolve('/' . $localPath));
        $this->assertSame($cloudUrl, MediaUrlResolver::resolve('storage/' . $localPath));
    }

    public function test_media_url_resolver_does_not_fabricate_cloudinary_url_for_unsynced_media(): void
    {
        // When a file is not in mapping, MediaUrlResolver should NOT fabricate a Cloudinary URL
        $localPath = 'articles/covers/unmigrated_article.jpg';

        $resolved = MediaUrlResolver::resolve($localPath);

        // Must not contain res.cloudinary.com since it hasn't been uploaded
        $this->assertSame($localPath, $resolved);
    }

    public function test_media_url_resolver_correctly_classifies_audio_and_image_resource_types(): void
    {
        $this->assertTrue(MediaUrlResolver::isAudio('mantras/audio/om_mani.mp3'));
        $this->assertTrue(MediaUrlResolver::isAudio('mantras/audio/great_compassion.wav'));
        $this->assertTrue(MediaUrlResolver::isAudio('mantras/audio/chant.m4a'));
        $this->assertFalse(MediaUrlResolver::isAudio('products/bell.jpg'));

        $this->assertTrue(MediaUrlResolver::isImage('products/statue.jpg'));
        $this->assertTrue(MediaUrlResolver::isImage('articles/covers/bodhi.webp'));
        $this->assertTrue(MediaUrlResolver::isImage('avatars/monk.png'));
        $this->assertFalse(MediaUrlResolver::isImage('audio/recitation.mp3'));

        // Cloudinary requires audio to be under resource_type: video
        $this->assertSame('video', MediaUrlResolver::getResourceType('mantras/audio/om_mani.mp3'));
        $this->assertSame('image', MediaUrlResolver::getResourceType('products/statue.jpg'));
        $this->assertSame('raw', MediaUrlResolver::getResourceType('source_documents/text.docx'));
    }

    public function test_media_url_resolver_extracts_clean_public_id(): void
    {
        $this->assertSame('products/item-1', MediaUrlResolver::extractPublicId('products/item-1.jpg'));
        $this->assertSame('mantras/audio/om_mani', MediaUrlResolver::extractPublicId('mantras/audio/om_mani.mp3'));
        $this->assertSame('temples/covers/pagoda', MediaUrlResolver::extractPublicId('/temples/covers/pagoda.png'));
    }

    public function test_media_storage_service_handles_unconfigured_state_safely(): void
    {
        $service = new MediaStorageService([
            'cloud_name' => null,
            'api_key' => null,
            'api_secret' => null,
        ]);
        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->existsInCloudinary('test.jpg'));
    }

    public function test_media_storage_service_does_not_delete_mapping_if_cloudinary_fails(): void
    {
        $localPath = 'articles/test_mapping_protection.jpg';

        $mapping = CloudinaryMediaMapping::create([
            'local_path' => $localPath,
            'public_id' => 'articles/test_mapping_protection',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => 'https://res.cloudinary.com/test/image/upload/articles/test_mapping_protection.jpg',
            'checksum' => hash('sha256', 'dummy'),
            'file_size' => 1234,
            'status' => 'synced',
        ]);

        $service = new MediaStorageService(['cloud_name' => null]);
        // Since Cloudinary is unconfigured, deletion must not delete mapping
        $result = $service->deleteFromCloudinary($localPath);

        $this->assertFalse($result);
        $this->assertDatabaseHas('cloudinary_media_mappings', [
            'id' => $mapping->id,
            'local_path' => $localPath,
        ]);
    }

    public function test_cloudinary_migrate_command_dry_run_executes_safely(): void
    {
        $manifestPath = storage_path('app/cloudinary_migration_manifest.json');
        if (File::exists($manifestPath)) {
            File::delete($manifestPath);
        }

        $this->artisan('cloudinary:migrate-media', [
            '--dry-run' => true,
            '--type' => 'all',
        ])->assertExitCode(0);
        $this->assertFileExists($manifestPath);

        $manifestContent = json_decode(File::get($manifestPath), true);
        $this->assertTrue($manifestContent['dry_run']);
        $this->assertArrayHasKey('stats', $manifestContent);
        $this->assertArrayHasKey('entries', $manifestContent);

        // Dry run must never write new synced mappings to database
        $this->assertSame(0, CloudinaryMediaMapping::where('status', 'synced')->count());
    }

    public function test_api_resources_output_resolved_media_urls(): void
    {
        $cloudUrl = 'https://res.cloudinary.com/ompramani/image/upload/products/statue.jpg';
        CloudinaryMediaMapping::create([
            'local_path' => 'products/statue.jpg',
            'public_id' => 'products/statue',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => $cloudUrl,
            'checksum' => hash('sha256', 'dummy'),
            'file_size' => 1024,
            'status' => 'synced',
        ]);

        $product = new Product([
            'name' => 'Tượng Bồ Tát',
            'slug' => 'tuong-bo-tat',
            'cover_image' => 'products/statue.jpg',
            'status' => 'published',
        ]);
        $product->id = 1;

        $resource = new ProductSummaryResource($product);
        $response = $resource->toArray(new Request());

        $this->assertSame($cloudUrl, $response['cover_image']);
    }

    public function test_filament_tables_configure_image_columns_cleanly(): void
    {
        // Set public disk to cloudinary to simulate production
        config(['filesystems.public_disk' => 'cloudinary']);

        $cloudUrl = 'https://res.cloudinary.com/ompramani/image/upload/sample.jpg';
        CloudinaryMediaMapping::create([
            'local_path' => 'sample.jpg',
            'public_id' => 'sample',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => $cloudUrl,
            'checksum' => hash('sha256', 'dummy'),
            'file_size' => 1024,
            'status' => 'synced',
        ]);

        $admin = \App\Models\User::factory()->admin()->create();
        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\Articles\Pages\ListArticles::class)->assertSuccessful();
        \Livewire\Livewire::test(\App\Filament\Resources\Products\Pages\ListProducts::class)->assertSuccessful();
        \Livewire\Livewire::test(\App\Filament\Resources\Temples\Pages\ListTemples::class)->assertSuccessful();
        \Livewire\Livewire::test(\App\Filament\Resources\BuddhistFigures\Pages\ListBuddhistFigures::class)->assertSuccessful();
        \Livewire\Livewire::test(\App\Filament\Resources\Sutras\Pages\ListSutras::class)->assertSuccessful();
        \Livewire\Livewire::test(\App\Filament\Resources\Mantras\Pages\ListMantras::class)->assertSuccessful();
    }

    public function test_audio_recitation_model_and_resource_resolve_video_resource_type_url(): void
    {
        $audioUrl = 'https://res.cloudinary.com/ompramani/video/upload/mantras/audio/chant.mp3';
        CloudinaryMediaMapping::create([
            'local_path' => 'mantras/audio/chant.mp3',
            'public_id' => 'mantras/audio/chant',
            'resource_type' => 'video',
            'format' => 'mp3',
            'secure_url' => $audioUrl,
            'checksum' => hash('sha256', 'dummy-audio'),
            'file_size' => 2048,
            'status' => 'synced',
        ]);

        $mantra = new Mantra([
            'title' => 'Thần Chú Đại Bi',
            'slug' => 'than-chu-dai-bi',
            'audio_path' => 'mantras/audio/chant.mp3',
            'status' => 'published',
        ]);

        $this->assertSame($audioUrl, $mantra->audio_url);

        $resource = new MantraResource($mantra);
        $data = $resource->toArray(new Request());
        $this->assertSame($audioUrl, $data['audio_url']);
    }
}
