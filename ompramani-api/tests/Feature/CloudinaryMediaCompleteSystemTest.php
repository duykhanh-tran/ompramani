<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Articles\Schemas\ArticleForm;
use App\Filament\Resources\BuddhistFigures\Schemas\BuddhistFigureForm;
use App\Filament\Resources\Mantras\Schemas\MantraForm;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Sutras\Schemas\SutraForm;
use App\Filament\Resources\Temples\Schemas\TempleForm;
use App\Http\Resources\Api\V1\ArticleDetailResource;
use App\Http\Resources\Api\V1\ArticleListResource;
use App\Http\Resources\Api\V1\BuddhistFigureResource;
use App\Http\Resources\Api\V1\MantraResource;
use App\Http\Resources\Api\V1\OrderItemResource;
use App\Http\Resources\Api\V1\OrderSummaryResource;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductSummaryResource;
use App\Http\Resources\Api\V1\RecitationAudioResource;
use App\Http\Resources\Api\V1\SutraBookmarkResource;
use App\Http\Resources\Api\V1\SutraReadingProgressResource;
use App\Http\Resources\Api\V1\SutraResource;
use App\Http\Resources\Api\V1\TempleDetailResource;
use App\Http\Resources\Api\V1\TempleSummaryResource;
use App\Http\Resources\Api\V1\UserProfileResource;
use App\Models\Article;
use App\Models\BuddhistFigure;
use App\Models\CloudinaryMediaMapping;
use App\Models\Mantra;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RecitationAudio;
use App\Models\Sutra;
use App\Models\SutraBookmark;
use App\Models\SutraReadingProgress;
use App\Models\Temple;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Media\MediaStorageService;
use App\Services\Media\MediaUrlResolver;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudinaryMediaCompleteSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        MediaUrlResolver::clearCache();
    }

    /**
     * 1. Test MediaUrlResolver sanitizes duplicate file extensions (.jpg.jpg -> .jpg).
     */
    public function test_media_url_resolver_sanitizes_duplicate_file_extensions(): void
    {
        $dirtyUrl = 'https://res.cloudinary.com/dml5eptq2/image/upload/v12345/articles/covers/cover.jpg.jpg';
        $cleaned = MediaUrlResolver::cleanDoubleExtension($dirtyUrl);
        $this->assertSame('https://res.cloudinary.com/dml5eptq2/image/upload/v12345/articles/covers/cover.jpg', $cleaned);

        $dirtyPng = 'products/sample.png.png';
        $this->assertSame('products/sample.png', MediaUrlResolver::cleanDoubleExtension($dirtyPng));

        $dirtyMp3 = 'mantras/audio/chant.mp3.mp3';
        $this->assertSame('mantras/audio/chant.mp3', MediaUrlResolver::cleanDoubleExtension($dirtyMp3));

        $dirtyResolved = MediaUrlResolver::resolve($dirtyUrl);
        $this->assertSame('https://res.cloudinary.com/dml5eptq2/image/upload/v12345/articles/covers/cover.jpg', $dirtyResolved);
    }

    /**
     * 2. Test MediaUrlResolver extracts clean public_id without file extension.
     */
    public function test_media_url_resolver_extracts_clean_public_id(): void
    {
        $path1 = 'articles/covers/01M6ZTCVP04NNANE81DF81CEWV.jpg';
        $this->assertSame('articles/covers/01M6ZTCVP04NNANE81DF81CEWV', MediaUrlResolver::extractPublicId($path1));

        $path2 = 'mantras/audio/01M784B2PWHN7YBFBCWXKBT5X2.mp3';
        $this->assertSame('mantras/audio/01M784B2PWHN7YBFBCWXKBT5X2', MediaUrlResolver::extractPublicId($path2));
    }

    /**
     * 3. Test buildCloudinaryUrl generates correct resource_type (video for audio, image for image).
     */
    public function test_build_cloudinary_url_respects_resource_types(): void
    {
        config(['filesystems.disks.cloudinary.cloud_name' => 'dml5eptq2']);

        $imageUrl = MediaUrlResolver::buildCloudinaryUrl('products/statue.jpg');
        $this->assertStringContainsString('/image/upload/', $imageUrl);
        $this->assertStringNotContainsString('.jpg.jpg', $imageUrl);

        $audioUrl = MediaUrlResolver::buildCloudinaryUrl('mantras/audio/bell.mp3');
        $this->assertStringContainsString('/video/upload/', $audioUrl);
        $this->assertStringNotContainsString('.mp3.mp3', $audioUrl);
    }

    /**
     * 4. Test MediaStorageService uploadToCloudinary creates mapping with clean public_id and no double extension.
     */
    public function test_upload_to_cloudinary_creates_mapping_in_database(): void
    {
        config(['filesystems.disks.cloudinary.cloud_name' => 'dml5eptq2']);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_img');
        file_put_contents($tempFile, 'fake-image-binary-data');

        // Mock Cloudinary client
        $mockCloudinary = \Mockery::mock(\Cloudinary\Cloudinary::class);
        $mockUploadApi = \Mockery::mock(\Cloudinary\Api\Upload\UploadApi::class);

        $mockUploadApi->shouldReceive('upload')
            ->once()
            ->withArgs(function ($source, $options) {
                // Assert public_id does NOT contain file extension
                return $options['public_id'] === 'articles/covers/test_article'
                    && $options['resource_type'] === 'image'
                    && $options['use_filename'] === false
                    && $options['unique_filename'] === false;
            })
            ->andReturn(new \Cloudinary\Api\ApiResponse([
                'public_id' => 'articles/covers/test_article',
                'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/v12345/articles/covers/test_article.jpg',
                'resource_type' => 'image',
                'format' => 'jpg',
                'bytes' => 1024,
            ], []));

        $mockCloudinary->shouldReceive('uploadApi')->andReturn($mockUploadApi);

        $service = new class($mockCloudinary) extends MediaStorageService {
            public function __construct($mockClient)
            {
                $this->cloudinary = $mockClient;
            }
        };

        $result = $service->uploadToCloudinary($tempFile, 'articles/covers/test_article.jpg');

        $this->assertSame('synced', $result['status']);
        $this->assertSame('articles/covers/test_article', $result['public_id']);
        $this->assertSame('https://res.cloudinary.com/dml5eptq2/image/upload/v12345/articles/covers/test_article.jpg', $result['secure_url']);

        // Assert database mapping was automatically created
        $this->assertDatabaseHas('cloudinary_media_mappings', [
            'local_path' => 'articles/covers/test_article.jpg',
            'public_id' => 'articles/covers/test_article',
            'resource_type' => 'image',
            'format' => 'jpg',
            'status' => 'synced',
        ]);

        unlink($tempFile);
    }

    /**
     * 5. Test audio upload sets resource_type to video in Cloudinary and database mapping.
     */
    public function test_audio_upload_sets_video_resource_type(): void
    {
        config(['filesystems.disks.cloudinary.cloud_name' => 'dml5eptq2']);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio');
        file_put_contents($tempFile, 'fake-mp3-binary-data');

        $mockCloudinary = \Mockery::mock(\Cloudinary\Cloudinary::class);
        $mockUploadApi = \Mockery::mock(\Cloudinary\Api\Upload\UploadApi::class);

        $mockUploadApi->shouldReceive('upload')
            ->once()
            ->withArgs(function ($source, $options) {
                return $options['resource_type'] === 'video'
                    && $options['public_id'] === 'mantras/audio/om_mani_padme_hum';
            })
            ->andReturn(new \Cloudinary\Api\ApiResponse([
                'public_id' => 'mantras/audio/om_mani_padme_hum',
                'secure_url' => 'https://res.cloudinary.com/dml5eptq2/video/upload/v12345/mantras/audio/om_mani_padme_hum.mp3',
                'resource_type' => 'video',
                'format' => 'mp3',
                'bytes' => 50000,
            ], []));

        $mockCloudinary->shouldReceive('uploadApi')->andReturn($mockUploadApi);

        $service = new class($mockCloudinary) extends MediaStorageService {
            public function __construct($mockClient)
            {
                $this->cloudinary = $mockClient;
            }
        };

        $result = $service->uploadToCloudinary($tempFile, 'mantras/audio/om_mani_padme_hum.mp3');

        $this->assertSame('video', $result['resource_type']);
        $this->assertStringContainsString('/video/upload/', $result['secure_url']);

        $this->assertDatabaseHas('cloudinary_media_mappings', [
            'local_path' => 'mantras/audio/om_mani_padme_hum.mp3',
            'resource_type' => 'video',
            'format' => 'mp3',
        ]);

        unlink($tempFile);
    }

    /**
     * 6. Test MediaStorageService::isPathUsedByOtherRecords protects media currently in use.
     */
    public function test_is_path_used_by_other_records_protects_active_media(): void
    {
        $service = new MediaStorageService(['cloud_name' => null]);

        $article = Article::factory()->create([
            'cover_image' => 'articles/covers/shared_image.jpg',
        ]);

        // Path is in use by Article
        $this->assertTrue($service->isPathUsedByOtherRecords('articles/covers/shared_image.jpg'));

        // Path is not in use by any other record when Article is excluded
        $this->assertFalse($service->isPathUsedByOtherRecords('articles/covers/shared_image.jpg', $article));

        // Unused path returns false
        $this->assertFalse($service->isPathUsedByOtherRecords('articles/covers/unused_image.jpg'));
    }

    protected function findFileUploadComponent(array $components, string $name): ?FileUpload
    {
        foreach ($components as $component) {
            if ($component instanceof FileUpload && $component->getName() === $name) {
                return $component;
            }

            try {
                $refClass = new \ReflectionClass($component);
                if ($refClass->hasProperty('childComponents')) {
                    $childProp = $refClass->getProperty('childComponents');
                    $rawChildren = $childProp->getValue($component);
                    if (is_array($rawChildren)) {
                        foreach ($rawChildren as $subList) {
                            if (is_array($subList)) {
                                $found = $this->findFileUploadComponent($subList, $name);
                                if ($found) {
                                    return $found;
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable) {
                // Ignore
            }
        }

        return null;
    }

    /**
     * 7. Test Filament Form FileUpload components have visibility('public') and fetchFileInformation(false).
     */
    public function test_filament_form_file_upload_components_configured_properly(): void
    {
        $checkFileUpload = function (FileUpload $upload): void {
            $fetchProp = new \ReflectionProperty(\Filament\Forms\Components\BaseFileUpload::class, 'shouldFetchFileInformation');
            $visProp = new \ReflectionProperty(\Filament\Forms\Components\BaseFileUpload::class, 'visibility');

            $this->assertFalse((bool) $fetchProp->getValue($upload));
            $this->assertSame('public', $visProp->getValue($upload));
        };

        // 1. ArticleForm
        $articleSchema = ArticleForm::configure(Schema::make());
        $articleCover = $this->findFileUploadComponent($articleSchema->getComponents(), 'cover_image');
        $this->assertInstanceOf(FileUpload::class, $articleCover);
        $checkFileUpload($articleCover);

        // 2. ProductForm
        $productSchema = ProductForm::configure(Schema::make());
        $productCover = $this->findFileUploadComponent($productSchema->getComponents(), 'cover_image');
        $this->assertInstanceOf(FileUpload::class, $productCover);
        $checkFileUpload($productCover);

        // 3. SutraForm
        $sutraSchema = SutraForm::configure(Schema::make());
        $sutraThumb = $this->findFileUploadComponent($sutraSchema->getComponents(), 'thumbnail');
        $this->assertInstanceOf(FileUpload::class, $sutraThumb);
        $checkFileUpload($sutraThumb);

        // 4. MantraForm
        $mantraSchema = MantraForm::configure(Schema::make());
        $mantraAudio = $this->findFileUploadComponent($mantraSchema->getComponents(), 'audio_path');
        $this->assertInstanceOf(FileUpload::class, $mantraAudio);
        $checkFileUpload($mantraAudio);

        // 5. TempleForm
        $templeSchema = TempleForm::configure(Schema::make());
        $templeCover = $this->findFileUploadComponent($templeSchema->getComponents(), 'cover_image');
        $this->assertInstanceOf(FileUpload::class, $templeCover);
        $checkFileUpload($templeCover);

        // 6. BuddhistFigureForm
        $figureSchema = BuddhistFigureForm::configure(Schema::make());
        $figureThumb = $this->findFileUploadComponent($figureSchema->getComponents(), 'thumbnail');
        $this->assertInstanceOf(FileUpload::class, $figureThumb);
        $checkFileUpload($figureThumb);
    }

    /**
     * 8. Test all API Resources resolve to HTTPS Cloudinary URL when mapping exists.
     */
    public function test_all_api_resources_return_https_urls_when_mapped(): void
    {
        $cloudCover = 'https://res.cloudinary.com/dml5eptq2/image/upload/v123/articles/covers/sample.jpg';
        CloudinaryMediaMapping::create([
            'local_path' => 'articles/covers/sample.jpg',
            'public_id' => 'articles/covers/sample',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => $cloudCover,
            'status' => 'synced',
        ]);

        $article = Article::factory()->create([
            'cover_image' => 'articles/covers/sample.jpg',
        ]);

        $req = Request::create('/api/v1/articles');
        $listRes = (new ArticleListResource($article))->toArray($req);
        $detailRes = (new ArticleDetailResource($article))->toArray($req);

        $this->assertSame($cloudCover, $listRes['cover_image']);
        $this->assertSame($cloudCover, $detailRes['cover_image']);

        // Sutra with mapping
        $cloudSutra = 'https://res.cloudinary.com/dml5eptq2/image/upload/v123/sutras/thumbnails/sutra.jpg';
        CloudinaryMediaMapping::create([
            'local_path' => 'sutras/thumbnails/sutra.jpg',
            'public_id' => 'sutras/thumbnails/sutra',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => $cloudSutra,
            'status' => 'synced',
        ]);

        $sutra = Sutra::factory()->create(['thumbnail' => 'sutras/thumbnails/sutra.jpg']);
        $sutraRes = (new SutraResource($sutra))->toArray($req);
        $this->assertSame($cloudSutra, $sutraRes['thumbnail']);

        // Order item with snapshot mapping
        $cloudProduct = 'https://res.cloudinary.com/dml5eptq2/image/upload/v123/products/statue.jpg';
        CloudinaryMediaMapping::create([
            'local_path' => 'products/statue.jpg',
            'public_id' => 'products/statue',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => $cloudProduct,
            'status' => 'synced',
        ]);

        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Đường Phật Giáo',
            'shipping_province' => 'TP. Hồ Chí Minh',
            'subtotal_vnd' => 500000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 530000,
            'currency' => 'VND',
            'order_status' => 'pending',
            'payment_method' => \App\Enums\PaymentMethod::Sepay,
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_public_id' => 'PRD-TEST-001',
            'product_name_snapshot' => 'Tượng Phật',
            'product_sku_snapshot' => 'SKU-001',
            'unit_price_vnd' => 500000,
            'quantity' => 1,
            'line_total_vnd' => 500000,
            'product_image_snapshot' => 'products/statue.jpg',
        ]);

        $itemRes = (new OrderItemResource($orderItem))->toArray($req);
        $this->assertSame($cloudProduct, $itemRes['product_image']);
    }

    /**
     * 9. Test ReconcileMediaMappingsCommand dry-run detects unmapped files and double extensions.
     */
    public function test_reconcile_command_dry_run_executes_safely(): void
    {
        // Create an unmapped article
        Article::factory()->create([
            'cover_image' => 'articles/covers/unmapped.jpg',
        ]);

        // Create a mapping with dirty double extension
        CloudinaryMediaMapping::create([
            'local_path' => 'products/dirty.jpg',
            'public_id' => 'products/dirty.jpg',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => 'https://res.cloudinary.com/demo/image/upload/products/dirty.jpg.jpg',
            'status' => 'synced',
        ]);

        $csvPath = storage_path('app/test_reconcile_report.csv');
        if (File::exists($csvPath)) {
            File::delete($csvPath);
        }

        $this->artisan('cloudinary:reconcile-media', [
            '--dry-run' => true,
            '--output-csv' => $csvPath,
        ])->assertExitCode(0);

        $this->assertFileExists($csvPath);

        $csvContent = File::get($csvPath);
        $this->assertStringContainsString('DOUBLE_EXT', $csvContent);

        if (File::exists($csvPath)) {
            File::delete($csvPath);
        }
    }

    /**
     * 10. Test ReconcileMediaMappingsCommand --fix repairs double extensions in mappings.
     */
    public function test_reconcile_command_fix_repairs_double_extensions(): void
    {
        $dirtyMapping = CloudinaryMediaMapping::create([
            'local_path' => 'products/item.jpg',
            'public_id' => 'products/item.jpg',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/products/item.jpg.jpg',
            'status' => 'synced',
        ]);

        $this->artisan('cloudinary:reconcile-media', [
            '--fix' => true,
        ])->assertExitCode(0);

        $dirtyMapping->refresh();
        $this->assertSame('https://res.cloudinary.com/dml5eptq2/image/upload/products/item.jpg', $dirtyMapping->secure_url);
        $this->assertSame('products/item', $dirtyMapping->public_id);
    }

    /**
     * 11. Test ReconcileMediaMappingsCommand detects and reports Sutra 7 mismatched figure asset.
     */
    public function test_reconcile_command_detects_sutra_7_mismatched_asset(): void
    {
        $sutra7 = Sutra::factory()->create([
            'id' => 7,
            'title' => 'Kinh A Di Đà',
            'thumbnail' => 'sutras/thumbnails/01M6Z19DFNRXQQ969R26W06B88.jpg',
        ]);

        // Simulated accidental mapping pointing to buddhist-figures
        CloudinaryMediaMapping::create([
            'local_path' => 'sutras/thumbnails/01M6Z19DFNRXQQ969R26W06B88.jpg',
            'public_id' => 'buddhist-figures/01M74SSE144ZASSK5R2CJJQQGE',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/v1/buddhist-figures/01M74SSE144ZASSK5R2CJJQQGE.jpg',
            'status' => 'synced',
        ]);

        $csvPath = storage_path('app/test_sutra7_report.csv');
        $this->artisan('cloudinary:reconcile-media', [
            '--dry-run' => true,
            '--output-csv' => $csvPath,
        ])->assertExitCode(0);

        $this->assertFileExists($csvPath);
        $csvContent = File::get($csvPath);
        $this->assertStringContainsString('MISMATCHED_ASSET', $csvContent);

        if (File::exists($csvPath)) {
            File::delete($csvPath);
        }
    }

    /**
     * 12. Test Sutra 7 with NO mapping is correctly flagged as MISSING_MAPPING (not clean).
     */
    public function test_reconcile_command_reports_sutra_7_as_missing_mapping_when_no_mapping_exists(): void
    {
        Sutra::factory()->create([
            'id' => 7,
            'title' => 'Kinh A Di Đà',
            'thumbnail' => 'sutras/thumbnails/authentic_sutra_thumb.jpg',
        ]);

        $csvPath = storage_path('app/test_sutra7_missing.csv');
        $this->artisan('cloudinary:reconcile-media', [
            '--dry-run' => true,
            '--output-csv' => $csvPath,
        ])->assertExitCode(0);

        $this->assertFileExists($csvPath);
        $csvContent = File::get($csvPath);
        $this->assertStringContainsString('sutras.id=7', $csvContent);
        $this->assertStringContainsString('MANUAL_ATTENTION', $csvContent);

        if (File::exists($csvPath)) {
            File::delete($csvPath);
        }
    }

    /**
     * 13. Test missing media without local file or Cloudinary asset is marked MANUAL_ATTENTION.
     */
    public function test_reconcile_command_flags_unverified_missing_media_as_manual_attention(): void
    {
        Article::factory()->create([
            'cover_image' => 'articles/covers/completely_missing_asset.jpg',
        ]);

        $csvPath = storage_path('app/test_manual_attention.csv');
        $this->artisan('cloudinary:reconcile-media', [
            '--dry-run' => true,
            '--output-csv' => $csvPath,
        ])->assertExitCode(0);

        $this->assertFileExists($csvPath);
        $csvContent = File::get($csvPath);
        $this->assertStringContainsString('UNVERIFIED_SOURCE_MISSING', $csvContent);
        $this->assertStringContainsString('MANUAL_ATTENTION', $csvContent);

        if (File::exists($csvPath)) {
            File::delete($csvPath);
        }
    }

    /**
     * 14. Test Sutra 7 restores mapping from existing Cloudinary asset without overwrite.
     */
    public function test_reconcile_command_restores_sutra7_from_existing_cloud_asset(): void
    {
        Sutra::factory()->create([
            'id' => 7,
            'title' => 'Kinh A Di Đà',
            'thumbnail' => 'sutras/thumbnails/known_sutra_cloud.jpg',
        ]);

        $mockStorage = \Mockery::mock(MediaStorageService::class);
        $mockStorage->shouldReceive('isConfigured')->andReturn(true);
        $mockStorage->shouldReceive('inspectCloudinaryAsset')
            ->with('sutras/thumbnails/known_sutra_cloud.jpg', 'image')
            ->andReturn([
                'status' => 'found',
                'asset' => [
                    'public_id' => 'sutras/thumbnails/known_sutra_cloud',
                    'resource_type' => 'image',
                    'format' => 'jpg',
                    'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/sutras/thumbnails/known_sutra_cloud.jpg',
                    'bytes' => 45000,
                ],
                'error' => null,
            ]);
        $mockStorage->shouldReceive('inspectCloudinaryAsset')->andReturn([
            'status' => 'not_found',
            'asset' => null,
            'error' => 'Not found',
        ]);
        $mockStorage->shouldReceive('uploadToCloudinary')->andReturn([
            'local_path' => 'sample',
            'public_id' => 'sample',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/sample.jpg',
            'checksum' => 'fake-sha',
            'file_size' => 1024,
            'status' => 'synced',
        ]);

        $this->app->instance(MediaStorageService::class, $mockStorage);

        $csvPath = storage_path('app/test_sutra7_cloud_restore.csv');
        $this->artisan('cloudinary:reconcile-media', [
            '--fix' => true,
            '--output-csv' => $csvPath,
        ])->assertExitCode(0);

        $mapping = CloudinaryMediaMapping::where('local_path', 'sutras/thumbnails/known_sutra_cloud.jpg')->first();
        $this->assertNotNull($mapping);
        $this->assertSame('https://res.cloudinary.com/dml5eptq2/image/upload/sutras/thumbnails/known_sutra_cloud.jpg', $mapping->secure_url);

        if (File::exists($csvPath)) {
            File::delete($csvPath);
        }
    }

    /**
     * 15. Test ReconcileMediaMappingsCommand supports target database connection parameter.
     */
    public function test_reconcile_command_supports_database_option(): void
    {
        $defaultConn = config('database.default');

        $this->artisan('cloudinary:reconcile-media', [
            '--dry-run' => true,
            '--database' => $defaultConn,
        ])->assertExitCode(0);

        // Ensure default connection was properly restored
        $this->assertSame($defaultConn, \Illuminate\Support\Facades\DB::getDefaultConnection());
    }

    /**
     * 16. Test CloudinaryStorageAdapter upload defaults to overwrite=false and blocks duplicate upload.
     */
    public function test_adapter_upload_defaults_to_overwrite_false_and_blocks_duplicate(): void
    {
        CloudinaryMediaMapping::create([
            'local_path' => 'products/existing_item.jpg',
            'public_id' => 'products/existing_item',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/products/existing_item.jpg',
            'status' => 'synced',
        ]);

        $mockCloudinary = \Mockery::mock(\Cloudinary\Cloudinary::class);
        $adapter = new \App\Services\Media\CloudinaryStorageAdapter($mockCloudinary);

        $this->expectException(\League\Flysystem\UnableToWriteFile::class);
        $this->expectExceptionMessage('overwrite is disabled');

        $adapter->write('products/existing_item.jpg', 'new-content', new \League\Flysystem\Config());
    }

    /**
     * 17. Test CloudinaryStorageAdapter preserves authentic double extension URL returned by Cloudinary.
     */
    public function test_adapter_upload_preserves_authentic_double_extension_from_cloudinary(): void
    {
        $mockCloudinary = \Mockery::mock(\Cloudinary\Cloudinary::class);
        $mockUploadApi = \Mockery::mock(\Cloudinary\Api\Upload\UploadApi::class);

        $mockUploadApi->shouldReceive('upload')
            ->once()
            ->andReturn(new \Cloudinary\Api\ApiResponse([
                'public_id' => 'products/item.jpg',
                'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/v1/products/item.jpg.jpg',
                'format' => 'jpg',
                'resource_type' => 'image',
                'bytes' => 2048,
            ], []));

        $mockCloudinary->shouldReceive('uploadApi')->andReturn($mockUploadApi);
        $adapter = new \App\Services\Media\CloudinaryStorageAdapter($mockCloudinary);

        $adapter->write('products/item.jpg', 'dummy-data', new \League\Flysystem\Config(['overwrite' => true]));

        $mapping = CloudinaryMediaMapping::where('local_path', 'products/item.jpg')->first();
        $this->assertNotNull($mapping);
        // Assert the exact URL was preserved and NOT stripped
        $this->assertSame('https://res.cloudinary.com/dml5eptq2/image/upload/v1/products/item.jpg.jpg', $mapping->secure_url);
    }

    /**
     * 18. Test CloudinaryStorageAdapter delete protects shared asset referenced by other records.
     */
    public function test_adapter_delete_protects_shared_asset_used_by_other_records(): void
    {
        Article::factory()->create([
            'cover_image' => 'articles/shared_hero.jpg',
        ]);

        $mapping = CloudinaryMediaMapping::create([
            'local_path' => 'articles/shared_hero.jpg',
            'public_id' => 'articles/shared_hero',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/articles/shared_hero.jpg',
            'status' => 'synced',
        ]);

        $mockCloudinary = \Mockery::mock(\Cloudinary\Cloudinary::class);
        $mockUploadApi = \Mockery::mock(\Cloudinary\Api\Upload\UploadApi::class);
        // destroy MUST NOT be called because asset is shared!
        $mockUploadApi->shouldNotReceive('destroy');
        $mockCloudinary->shouldReceive('uploadApi')->andReturn($mockUploadApi);

        $adapter = new \App\Services\Media\CloudinaryStorageAdapter($mockCloudinary);
        $adapter->delete('articles/shared_hero.jpg');

        // Mapping must still exist
        $this->assertDatabaseHas('cloudinary_media_mappings', [
            'local_path' => 'articles/shared_hero.jpg',
        ]);
    }

    /**
     * 19. Test CloudinaryStorageAdapter delete only deletes mapping when Cloudinary confirms success.
     */
    public function test_adapter_delete_only_deletes_mapping_on_cloudinary_success(): void
    {
        $mapping = CloudinaryMediaMapping::create([
            'local_path' => 'products/unshared_temp.jpg',
            'public_id' => 'products/unshared_temp',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/products/unshared_temp.jpg',
            'status' => 'synced',
        ]);

        $mockCloudinary = \Mockery::mock(\Cloudinary\Cloudinary::class);
        $mockUploadApi = \Mockery::mock(\Cloudinary\Api\Upload\UploadApi::class);

        // When destroy returns failure status
        $mockUploadApi->shouldReceive('destroy')
            ->once()
            ->andReturn(new \Cloudinary\Api\ApiResponse(['result' => 'error'], []));
        $mockCloudinary->shouldReceive('uploadApi')->andReturn($mockUploadApi);

        $adapter = new \App\Services\Media\CloudinaryStorageAdapter($mockCloudinary);

        try {
            $adapter->delete('products/unshared_temp.jpg');
            $this->fail('Expected UnableToDeleteFile was not thrown');
        } catch (\League\Flysystem\UnableToDeleteFile $e) {
            $this->assertStringContainsString('Cloudinary destroy rejected', $e->getMessage());
        }

        // Mapping must still be preserved
        $this->assertDatabaseHas('cloudinary_media_mappings', [
            'local_path' => 'products/unshared_temp.jpg',
        ]);
    }

    /**
     * 20. Test ReconcileMediaMappingsCommand handles Cloudinary API rate limits and errors gracefully.
     */
    public function test_reconcile_command_handles_api_rate_limits_safely(): void
    {
        Sutra::factory()->create([
            'id' => 7,
            'title' => 'Kinh A Di Đà',
            'thumbnail' => 'sutras/thumbnails/sutra7_rate_limit.jpg',
        ]);

        $mockStorage = \Mockery::mock(MediaStorageService::class);
        $mockStorage->shouldReceive('isConfigured')->andReturn(true);
        $mockStorage->shouldReceive('inspectCloudinaryAsset')
            ->with('sutras/thumbnails/sutra7_rate_limit.jpg', 'image')
            ->andReturn([
                'status' => 'rate_limited',
                'asset' => null,
                'error' => 'API Rate Limit Exceeded (HTTP 429)',
            ]);
        $mockStorage->shouldReceive('inspectCloudinaryAsset')->andReturn([
            'status' => 'not_found',
            'asset' => null,
            'error' => 'Not found',
        ]);

        $this->app->instance(MediaStorageService::class, $mockStorage);

        $csvPath = storage_path('app/test_rate_limit_report.csv');
        $this->artisan('cloudinary:reconcile-media', [
            '--dry-run' => true,
            '--output-csv' => $csvPath,
        ])->assertExitCode(0);

        $this->assertFileExists($csvPath);
        $csvContent = File::get($csvPath);
        $this->assertStringContainsString('API_RATE_LIMITED', $csvContent);

        if (File::exists($csvPath)) {
            File::delete($csvPath);
        }
    }

    /**
     * 21. Test ReconcileMediaMappingsCommand recovers pending failed mappings from recovery queue.
     */
    public function test_reconcile_command_recovers_pending_mappings_from_queue(): void
    {
        $recoveryFile = storage_path('app/pending_media_mappings_recovery.json');
        File::ensureDirectoryExists(dirname($recoveryFile));
        File::put($recoveryFile, json_encode([
            'products/queued_recovery.jpg' => [
                'local_path' => 'products/queued_recovery.jpg',
                'public_id' => 'products/queued_recovery',
                'resource_type' => 'image',
                'format' => 'jpg',
                'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/products/queued_recovery.jpg',
                'checksum' => 'recovery-sha',
                'file_size' => 4096,
                'failed_at' => now()->toIso8601String(),
            ],
        ]));

        $mockStorage = \Mockery::mock(MediaStorageService::class);
        $mockStorage->shouldReceive('isConfigured')->andReturn(true);
        $mockStorage->shouldReceive('inspectCloudinaryAsset')->andReturn([
            'status' => 'not_found',
            'asset' => null,
            'error' => 'Not found',
        ]);
        $mockStorage->shouldReceive('uploadToCloudinary')->andReturn([
            'local_path' => 'sample',
            'public_id' => 'sample',
            'resource_type' => 'image',
            'format' => 'jpg',
            'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/sample.jpg',
            'checksum' => 'sample-sha',
            'file_size' => 1024,
            'status' => 'synced',
        ]);
        $this->app->instance(MediaStorageService::class, $mockStorage);

        $this->artisan('cloudinary:reconcile-media', [
            '--fix' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('cloudinary_media_mappings', [
            'local_path' => 'products/queued_recovery.jpg',
            'secure_url' => 'https://res.cloudinary.com/dml5eptq2/image/upload/products/queued_recovery.jpg',
        ]);

        // Recovery file must be cleared after successful fix
        $this->assertFileDoesNotExist($recoveryFile);
    }
}
