<?php

namespace Tests\Feature;

use App\Enums\TemplePublicationStatus;
use App\Enums\TempleType;
use App\Filament\Resources\Temples\Pages\CreateTemple;
use App\Filament\Resources\Temples\Pages\EditTemple;
use App\Filament\Resources\Temples\Schemas\TempleForm;
use App\Filament\Resources\Temples\TempleResource;
use App\Models\Temple;
use App\Models\Tradition;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TempleAdminSimplificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * 1. TempleForm has exactly 12 components.
     * 2. Component names match the 12 core fields including google_maps_url.
     * 3. Non-core fields (latitude, longitude, phone, email, website, review_rating, etc.) are absent.
     */
    public function test_temple_form_has_exactly_12_core_fields(): void
    {
        $schema = new Schema;
        $configured = TempleForm::configure($schema);
        $components = $configured->getComponents();

        $this->assertCount(12, $components, 'TempleForm must have exactly 12 fields.');

        $names = array_map(fn ($c) => $c->getName(), $components);

        $expectedNames = [
            'name',
            'traditions',
            'construction_year',
            'opening_hours',
            'admission_fee',
            'address_line',
            'google_maps_url',
            'cover_image',
            'short_description',
            'highlights',
            'history',
            'architecture_culture',
        ];

        $this->assertEquals($expectedNames, $names);

        // Ensure non-core removed fields are not present
        $this->assertNotContains('slug', $names);
        $this->assertNotContains('latitude', $names);
        $this->assertNotContains('longitude', $names);
        $this->assertNotContains('place_type', $names);
        $this->assertNotContains('publication_status', $names);
        $this->assertNotContains('phone', $names);
        $this->assertNotContains('email', $names);
        $this->assertNotContains('website_url', $names);
        $this->assertNotContains('google_maps_place_id', $names);
        $this->assertNotContains('review_rating', $names);
        $this->assertNotContains('review_count', $names);
        $this->assertNotContains('contact_links', $names);
        $this->assertNotContains('sources', $names);
        $this->assertNotContains('media', $names);
    }

    /**
     * 4. Verify component types and configurations.
     */
    public function test_temple_form_component_types(): void
    {
        $schema = new Schema;
        $configured = TempleForm::configure($schema);
        $components = collect($configured->getComponents())->keyBy(fn ($c) => $c->getName());

        $this->assertInstanceOf(TextInput::class, $components['name']);
        $this->assertInstanceOf(Select::class, $components['traditions']);
        $this->assertInstanceOf(TextInput::class, $components['construction_year']);
        $this->assertInstanceOf(TextInput::class, $components['opening_hours']);
        $this->assertInstanceOf(TextInput::class, $components['admission_fee']);
        $this->assertInstanceOf(TextInput::class, $components['address_line']);
        $this->assertInstanceOf(TextInput::class, $components['google_maps_url']);
        $this->assertInstanceOf(FileUpload::class, $components['cover_image']);
        $this->assertInstanceOf(Textarea::class, $components['short_description']);
        $this->assertInstanceOf(Textarea::class, $components['highlights']);
        $this->assertInstanceOf(RichEditor::class, $components['history']);
        $this->assertInstanceOf(RichEditor::class, $components['architecture_culture']);

        /** @var FileUpload $coverImage */
        $coverImage = $components['cover_image'];
        $this->assertSame('public', $coverImage->getDiskName());
        $this->assertSame('temples/covers', $coverImage->getDirectory());
        $this->assertSame(5120, $coverImage->getMaxSize());
    }

    /**
     * 5. TempleResource has no relation managers on form pages.
     */
    public function test_temple_resource_has_no_relation_managers(): void
    {
        $relations = TempleResource::getRelations();
        $this->assertEmpty($relations, 'TempleResource relation managers must be empty for simplified UI.');
    }

    /**
     * 6. Livewire test: CreateTemple with 11 fields.
     * Automatically sets slug and defaults publication_status to Draft.
     */
    public function test_admin_can_create_temple_via_filament_form(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $tradition = Tradition::factory()->create(['name' => 'Bắc Tông']);
        $fakeImage = UploadedFile::fake()->image('chua-hoang-phap.jpg', 800, 600);

        Livewire::test(CreateTemple::class)
            ->fillForm([
                'name' => 'Chùa Hoằng Pháp Tân Tạo',
                'traditions' => [$tradition->id],
                'construction_year' => '1957',
                'opening_hours' => '05:00 - 20:30',
                'admission_fee' => 0,
                'address_line' => 'Xã Tân Hiệp, Huyện Hóc Môn, TP. Hồ Chí Minh',
                'cover_image' => $fakeImage,
                'short_description' => 'Trung tâm hoằng pháp lớn và trang nghiêm.',
                'highlights' => 'Khóa tu Phật thất quy mô hàng nghìn Phật tử.',
                'history' => '<p>Được kiến tạo vào năm 1957...</p>',
                'architecture_culture' => '<p>Kiến trúc mang đậm nét truyền thống Á Đông...</p>',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $temple = Temple::where('name', 'Chùa Hoằng Pháp Tân Tạo')->first();
        $this->assertNotNull($temple);

        // Core fields
        $this->assertSame('chua-hoang-phap-tan-tao', $temple->slug);
        $this->assertSame('1957', $temple->construction_year);
        $this->assertSame('05:00 - 20:30', $temple->opening_hours);
        $this->assertSame(0, $temple->admission_fee);
        $this->assertSame('Xã Tân Hiệp, Huyện Hóc Môn, TP. Hồ Chí Minh', $temple->address_line);
        $this->assertSame('Trung tâm hoằng pháp lớn và trang nghiêm.', $temple->short_description);
        $this->assertSame('Khóa tu Phật thất quy mô hàng nghìn Phật tử.', $temple->highlights);
        $this->assertSame('<p>Được kiến tạo vào năm 1957...</p>', $temple->history);
        $this->assertSame('<p>Kiến trúc mang đậm nét truyền thống Á Đông...</p>', $temple->architecture_culture);

        // Defaults
        $this->assertEquals(TemplePublicationStatus::Draft, $temple->publication_status);
        $this->assertEquals(TempleType::Temple, $temple->place_type);
        $this->assertSame('VN', $temple->country_code);

        // Media sync
        $this->assertNotEmpty($temple->cover_image);
        $this->assertTrue(Storage::disk('public')->exists($temple->cover_image));
        $this->assertTrue($temple->media()->where('is_cover', true)->exists());
    }

    protected function createTemple(array $attributes = []): Temple
    {
        return Temple::create(array_merge([
            'name' => 'Chùa Mẫu Mặc Định',
            'place_type' => TempleType::Temple,
            'country_code' => 'VN',
            'publication_status' => TemplePublicationStatus::Published,
            'published_at' => now(),
        ], $attributes));
    }

    /**
     * 7. Livewire test: EditTemple keeps slug stable on update.
     */
    public function test_editing_temple_name_does_not_change_slug(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $temple = $this->createTemple([
            'name' => 'Chùa Một Cột',
            'slug' => 'chua-mot-cot',
            'publication_status' => TemplePublicationStatus::Published,
        ]);

        Livewire::test(EditTemple::class, ['record' => $temple->getKey()])
            ->fillForm([
                'name' => 'Chùa Một Cột (Diên Hựu Tự)',
                'opening_hours' => '07:00 - 18:00',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $temple->refresh();
        $this->assertSame('Chùa Một Cột (Diên Hựu Tự)', $temple->name);
        $this->assertSame('chua-mot-cot', $temple->slug, 'Slug must remain unchanged on edit');
        $this->assertSame('07:00 - 18:00', $temple->opening_hours);
    }

    /**
     * 8. Public API GET /api/v1/temples/{slug} returns simplified fields.
     */
    public function test_public_temple_detail_api_returns_core_simplified_fields(): void
    {
        $temple = $this->createTemple([
            'name' => 'Chùa Bái Đính Cổ',
            'slug' => 'chua-bai-dinh-co',
            'publication_status' => TemplePublicationStatus::Published,
            'construction_year' => '1136',
            'opening_hours' => '06:00 - 18:00',
            'admission_fee' => 50000,
            'highlights' => 'Hang Sáng, Động Tối, Đền thờ Thần Cao Sơn.',
            'history' => '<p>Ngôi chùa cổ nghìn năm tuổi...</p>',
            'architecture_culture' => '<p>Kiến trúc hang động độc đáo...</p>',
        ]);

        $response = $this->getJson('/api/v1/temples/chua-bai-dinh-co');

        $response->assertOk()
            ->assertJsonPath('data.construction_year', '1136')
            ->assertJsonPath('data.opening_hours', '06:00 - 18:00')
            ->assertJsonPath('data.admission_fee', 50000)
            ->assertJsonPath('data.highlights', 'Hang Sáng, Động Tối, Đền thờ Thần Cao Sơn.')
            ->assertJsonPath('data.history', '<p>Ngôi chùa cổ nghìn năm tuổi...</p>')
            ->assertJsonPath('data.architecture_culture', '<p>Kiến trúc hang động độc đáo...</p>');
    }

    /**
     * 9. Public API GET /api/v1/temples list returns simplified summary fields.
     */
    public function test_public_temple_list_api_returns_core_simplified_fields(): void
    {
        $this->createTemple([
            'name' => 'Chùa Vĩnh Nghiêm',
            'slug' => 'chua-vinh-nghiem',
            'publication_status' => TemplePublicationStatus::Published,
            'construction_year' => '1964',
            'opening_hours' => '07:00 - 19:00',
            'admission_fee' => 0,
        ]);

        $response = $this->getJson('/api/v1/temples');

        $response->assertOk()
            ->assertJsonFragment([
                'slug' => 'chua-vinh-nghiem',
                'construction_year' => '1964',
                'opening_hours' => '07:00 - 19:00',
                'admission_fee' => 0,
            ]);
    }

    /**
     * 10. Province search scope matches both province_name and address_line.
     */
    public function test_province_search_matches_address_line_for_simplified_records(): void
    {
        $this->createTemple([
            'name' => 'Chùa Tây Phương',
            'address_line' => 'Thạch Thất, Hà Nội, Việt Nam',
            'province_name' => null,
            'publication_status' => TemplePublicationStatus::Published,
        ]);

        $results = Temple::query()->byProvince('Hà Nội')->get();
        $this->assertTrue($results->contains('name', 'Chùa Tây Phương'));
    }

    /**
     * 11. GoogleMapsUrl validation rules: valid formats accepted, insecure / invalid formats rejected.
     */
    public function test_google_maps_url_validation_rule(): void
    {
        $rule = new \App\Rules\GoogleMapsUrl;

        // Valid URLs
        $this->assertTrue(\App\Rules\GoogleMapsUrl::isValid('https://maps.app.goo.gl/9yZ4p8K7N2c'));
        $this->assertTrue(\App\Rules\GoogleMapsUrl::isValid('https://goo.gl/maps/1v4nABCxyz'));
        $this->assertTrue(\App\Rules\GoogleMapsUrl::isValid('https://www.google.com/maps/place/Chùa+Trấn+Quốc'));
        $this->assertTrue(\App\Rules\GoogleMapsUrl::isValid('https://maps.google.com/?cid=123456789'));
        $this->assertTrue(\App\Rules\GoogleMapsUrl::isValid('https://www.google.com.vn/maps/place/Chùa+Một+Cột'));
        $this->assertTrue(\App\Rules\GoogleMapsUrl::isValid('https://maps.google.com.vn/?q=chua'));

        // Insecure: HTTP rejected
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('http://maps.google.com/?cid=123456789'));
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('http://www.google.com/maps/place/Chua'));

        // Dangerous / non-HTTP protocols
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('javascript:alert(1)'));
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('data:text/html,<script>alert(1)</script>'));
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('file:///etc/passwd'));

        // Malicious userinfo credentials
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('https://user:pass@maps.google.com/'));

        // Lookalike / fake domains rejected
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('https://evil-google.com/maps'));
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('https://google.com.attacker.com/maps'));
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('https://notgooglemaps.com/xyz'));

        // Non-maps Google URLs rejected
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('https://www.google.com/search?q=chua'));
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid('https://mail.google.com/mail'));

        // Empty string
        $this->assertFalse(\App\Rules\GoogleMapsUrl::isValid(''));
    }

    /**
     * 12. Admin can create temple with valid Google Maps URL.
     */
    public function test_admin_can_create_temple_with_valid_google_maps_url(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateTemple::class)
            ->fillForm([
                'name' => 'Chùa Trấn Quốc Hà Nội',
                'address_line' => 'Thanh Niên, Yên Phụ, Tây Hồ, Hà Nội',
                'google_maps_url' => 'https://maps.app.goo.gl/TranQuocPagodaSample',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $temple = Temple::where('name', 'Chùa Trấn Quốc Hà Nội')->first();
        $this->assertNotNull($temple);
        $this->assertSame('https://maps.app.goo.gl/TranQuocPagodaSample', $temple->google_maps_url);
    }

    /**
     * 13. Admin creating temple with invalid Google Maps URL fails form validation.
     */
    public function test_admin_cannot_create_temple_with_invalid_google_maps_url(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateTemple::class)
            ->fillForm([
                'name' => 'Chùa Lỗi Link',
                'address_line' => 'Hà Nội',
                'google_maps_url' => 'http://insecure-domain.com/maps',
            ])
            ->call('create')
            ->assertHasFormErrors(['google_maps_url']);
    }

    /**
     * 14. Admin can update temple Google Maps URL.
     */
    public function test_admin_can_update_temple_google_maps_url(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $temple = $this->createTemple([
            'name' => 'Chùa Hương Tích',
            'google_maps_url' => null,
        ]);

        Livewire::test(EditTemple::class, ['record' => $temple->getKey()])
            ->fillForm([
                'google_maps_url' => 'https://www.google.com/maps/place/Chua+Huong',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $temple->refresh();
        $this->assertSame('https://www.google.com/maps/place/Chua+Huong', $temple->google_maps_url);
    }

    /**
     * 15. Temple API detail & list return google_maps_url.
     */
    public function test_temple_api_returns_google_maps_url(): void
    {
        $templeWithMap = $this->createTemple([
            'name' => 'Chùa Quán Sứ',
            'slug' => 'chua-quan-su',
            'publication_status' => TemplePublicationStatus::Published,
            'google_maps_url' => 'https://maps.app.goo.gl/QuanSuSample',
        ]);

        $templeWithoutMap = $this->createTemple([
            'name' => 'Chùa Không Có Map',
            'slug' => 'chua-khong-co-map',
            'publication_status' => TemplePublicationStatus::Published,
            'google_maps_url' => null,
        ]);

        // Detail endpoint with map
        $res1 = $this->getJson('/api/v1/temples/chua-quan-su');
        $res1->assertOk()
            ->assertJsonPath('data.google_maps_url', 'https://maps.app.goo.gl/QuanSuSample');

        // Detail endpoint without map
        $res2 = $this->getJson('/api/v1/temples/chua-khong-co-map');
        $res2->assertOk()
            ->assertJsonPath('data.google_maps_url', null);

        // List endpoint
        $res3 = $this->getJson('/api/v1/temples');
        $res3->assertOk()
            ->assertJsonFragment([
                'slug' => 'chua-quan-su',
                'google_maps_url' => 'https://maps.app.goo.gl/QuanSuSample',
            ])
            ->assertJsonFragment([
                'slug' => 'chua-khong-co-map',
                'google_maps_url' => null,
            ]);
    }
}
