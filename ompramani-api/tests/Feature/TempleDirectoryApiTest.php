<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TempleCoordinateAccuracy;
use App\Enums\TemplePublicAccessStatus;
use App\Enums\TemplePublicationStatus;
use App\Enums\TempleSourceType;
use App\Enums\TempleType;
use App\Enums\TempleVerificationStatus;
use App\Models\Temple;
use App\Models\TempleMedia;
use App\Models\TempleSource;
use App\Models\Tradition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempleDirectoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createPublishedTemple(array $attributes = []): Temple
    {
        return Temple::create(array_merge([
            'name' => 'Chùa Trấn Quốc',
            'place_type' => TempleType::Temple,
            'province_name' => 'Hà Nội',
            'country_code' => 'VN',
            'publication_status' => TemplePublicationStatus::Published,
            'published_at' => now()->subDay(),
            'verification_status' => TempleVerificationStatus::Verified,
        ], $attributes));
    }

    // =========================================================================
    // 1. PUBLIC LIST API
    // =========================================================================

    public function test_guest_can_list_published_temples_only(): void
    {
        $published = $this->createPublishedTemple(['name' => 'Chùa A Đã Xuất Bản']);

        Temple::create([
            'name' => 'Chùa Bản Nháp',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::Draft,
        ]);

        Temple::create([
            'name' => 'Chùa Đang Duyệt',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::InReview,
        ]);

        Temple::create([
            'name' => 'Chùa Đã Lưu Trữ',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::Archived,
        ]);

        $response = $this->getJson('/api/v1/temples');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $published->slug)
            ->assertJsonPath('data.0.name', 'Chùa A Đã Xuất Bản')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'public_id',
                        'slug',
                        'name',
                        'place_type',
                        'place_type_label',
                        'short_description',
                        'formatted_address',
                        'province_name',
                        'country_code',
                        'cover_image',
                        'traditions',
                        'has_verified_coordinates',
                        'published_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_list_pagination_and_max_per_page_limit(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->createPublishedTemple(['name' => sprintf('Chùa Tháp %02d', $i)]);
        }

        // Default per_page is 12
        $resDefault = $this->getJson('/api/v1/temples');
        $resDefault->assertOk()
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.per_page', 12);

        // Custom per_page = 5
        $resCustom = $this->getJson('/api/v1/temples?per_page=5');
        $resCustom->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);

        // Limit capped at max 50: per_page=100 rejected by validation
        $resOver = $this->getJson('/api/v1/temples?per_page=100');
        $resOver->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_deterministic_ordering(): void
    {
        $this->createPublishedTemple(['name' => 'Chùa Chiêm Bái C']);
        $this->createPublishedTemple(['name' => 'Chùa An Lạc A']);
        $this->createPublishedTemple(['name' => 'Chùa Bình An B']);

        $response = $this->getJson('/api/v1/temples');
        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertSame(['Chùa An Lạc A', 'Chùa Bình An B', 'Chùa Chiêm Bái C'], $names);
    }

    public function test_filter_by_place_type(): void
    {
        $this->createPublishedTemple(['name' => 'Chùa Hương', 'place_type' => TempleType::Temple]);
        $this->createPublishedTemple(['name' => 'Thiền Viện Trúc Lâm', 'place_type' => TempleType::MeditationMonastery]);
        $this->createPublishedTemple(['name' => 'Tịnh Xá Trung Tâm', 'place_type' => TempleType::Vihara]);

        $res = $this->getJson('/api/v1/temples?place_type=meditation_monastery');
        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Thiền Viện Trúc Lâm');
    }

    public function test_filter_by_province(): void
    {
        $this->createPublishedTemple(['name' => 'Chùa Một Cột', 'province_name' => 'Hà Nội']);
        $this->createPublishedTemple(['name' => 'Chùa Linh Ứng', 'province_name' => 'Đà Nẵng']);
        $this->createPublishedTemple(['name' => 'Chùa Thiên Mụ', 'province_name' => 'Thừa Thiên Huế']);

        $res = $this->getJson('/api/v1/temples?province=Đà Nẵng');
        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chùa Linh Ứng');
    }

    public function test_filter_by_country(): void
    {
        $this->createPublishedTemple(['name' => 'Chùa Việt Nam Quốc Tự', 'country_code' => 'VN']);
        $this->createPublishedTemple(['name' => 'Chùa Phật Quang Nhật Bản', 'country_code' => 'JP']);

        $res = $this->getJson('/api/v1/temples?country=JP');
        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chùa Phật Quang Nhật Bản');
    }

    public function test_filter_by_tradition(): void
    {
        $trad1 = Tradition::create(['name' => 'Nam Truyền', 'slug' => 'nam-truyen', 'is_active' => true]);
        $trad2 = Tradition::create(['name' => 'Khất Sĩ', 'slug' => 'khat-si', 'is_active' => true]);

        $t1 = $this->createPublishedTemple(['name' => 'Chùa Nam Tông']);
        $t1->traditions()->attach($trad1->id);

        $t2 = $this->createPublishedTemple(['name' => 'Tịnh Xá Ngọc Phương']);
        $t2->traditions()->attach($trad2->id);

        $res = $this->getJson('/api/v1/temples?tradition=nam-truyen');
        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chùa Nam Tông');
    }

    // =========================================================================
    // 2. PUBLIC DETAIL API
    // =========================================================================

    public function test_guest_can_view_published_temple_detail(): void
    {
        $temple = $this->createPublishedTemple([
            'name' => 'Chùa Giác Lâm',
            'short_description' => 'Ngôi chùa cổ nhất Sài Gòn.',
            'description' => 'Được xây dựng vào năm 1744.',
            'address_line' => '565 Lạc Long Quân',
            'ward_name' => 'Phường 10',
            'district_name' => 'Quận Tân Bình',
            'province_name' => 'TP. Hồ Chí Minh',
            'country_code' => 'VN',
            'latitude' => 10.7711,
            'longitude' => 106.6508,
            'coordinate_accuracy' => TempleCoordinateAccuracy::ExactVerified,
            'verification_status' => TempleVerificationStatus::Verified,
            'public_access_status' => TemplePublicAccessStatus::PubliclyAccessible,
            'public_phone' => '028 3865 3933',
            'public_email' => 'giaclam@ompramani.vn',
            'official_website' => 'https://chuagiaclam.vn',
        ]);

        TempleSource::create([
            'temple_id' => $temple->id,
            'source_title' => 'Cục Di sản Văn hóa Việt Nam',
            'source_url' => 'http://dsvh.gov.vn',
            'source_type' => TempleSourceType::GovernmentRegistry,
            'reference_note' => 'Hồ sơ xếp hạng Di tích Lịch sử - Văn hóa cấp Quốc gia.',
            'checked_at' => now(),
        ]);

        TempleMedia::create([
            'temple_id' => $temple->id,
            'file_path' => 'temples/giac-lam-cover.jpg',
            'alt_text' => 'Chính điện chùa Giác Lâm',
            'is_cover' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson("/api/v1/temples/{$temple->slug}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Chùa Giác Lâm')
            ->assertJsonPath('data.slug', $temple->slug)
            ->assertJsonPath('data.place_type', 'temple')
            ->assertJsonPath('data.place_type_label', 'Chùa')
            ->assertJsonPath('data.address.formatted', '565 Lạc Long Quân, Phường 10, Quận Tân Bình, TP. Hồ Chí Minh, Việt Nam')
            ->assertJsonPath('data.coordinates.latitude', 10.7711)
            ->assertJsonPath('data.coordinates.longitude', 106.6508)
            ->assertJsonPath('data.coordinates.accuracy', 'exact_verified')
            ->assertJsonPath('data.contacts.phone', '028 3865 3933')
            ->assertJsonPath('data.contacts.email', 'giaclam@ompramani.vn')
            ->assertJsonPath('data.contacts.website', 'https://chuagiaclam.vn')
            ->assertJsonCount(1, 'data.sources')
            ->assertJsonPath('data.sources.0.title', 'Cục Di sản Văn hóa Việt Nam')
            ->assertJsonCount(1, 'data.images')
            ->assertJsonPath('data.cover_image', 'temples/giac-lam-cover.jpg');
    }

    public function test_draft_and_archived_detail_return_404(): void
    {
        $draft = Temple::create([
            'name' => 'Chùa Nháp',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::Draft,
        ]);

        $archived = Temple::create([
            'name' => 'Chùa Cũ Lưu Trữ',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::Archived,
        ]);

        $this->getJson("/api/v1/temples/{$draft->slug}")->assertNotFound();
        $this->getJson("/api/v1/temples/{$archived->slug}")->assertNotFound();
        $this->getJson('/api/v1/temples/non-existent-slug')->assertNotFound();
    }

    // =========================================================================
    // 3. COORDINATE PRIVACY SAFEGUARDS
    // =========================================================================

    public function test_unverified_coordinates_hidden_from_public_api(): void
    {
        $temple = $this->createPublishedTemple([
            'name' => 'Chùa Có Tọa Độ Chưa Xác Minh',
            'latitude' => 20.1234,
            'longitude' => 105.1234,
            'coordinate_accuracy' => TempleCoordinateAccuracy::Approximate,
            'verification_status' => TempleVerificationStatus::PendingReview,
        ]);

        $response = $this->getJson("/api/v1/temples/{$temple->slug}");
        $response->assertOk()
            ->assertJsonPath('data.coordinates', null);

        $listRes = $this->getJson('/api/v1/temples');
        $listRes->assertOk()
            ->assertJsonPath('data.0.has_verified_coordinates', false);
    }

    public function test_restricted_temple_hides_coordinates_from_public_api(): void
    {
        $temple = $this->createPublishedTemple([
            'name' => 'Nội Viện Nhập Định Đóng Cửa',
            'latitude' => 11.1234,
            'longitude' => 106.1234,
            'coordinate_accuracy' => TempleCoordinateAccuracy::ExactVerified,
            'verification_status' => TempleVerificationStatus::Verified,
            'public_access_status' => TemplePublicAccessStatus::Restricted,
        ]);

        $response = $this->getJson("/api/v1/temples/{$temple->slug}");
        $response->assertOk()
            ->assertJsonPath('data.coordinates', null);
    }

    // =========================================================================
    // 4. TYPES REGISTRY API
    // =========================================================================

    public function test_types_endpoint_returns_static_registry(): void
    {
        $response = $this->getJson('/api/v1/temples/types');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'key',
                        'label',
                        'description',
                    ],
                ],
            ]);

        $keys = collect($response->json('data'))->pluck('key')->all();
        $this->assertContains('temple', $keys);
        $this->assertContains('meditation_monastery', $keys);
        $this->assertContains('vihara', $keys);
        $this->assertContains('monastery', $keys);
    }

    // =========================================================================
    // 5. CACHE INVALIDATION
    // =========================================================================

    public function test_cache_invalidation_on_temple_update(): void
    {
        $temple = $this->createPublishedTemple([
            'name' => 'Chùa Ban Đầu',
            'short_description' => 'Mô tả ban đầu',
        ]);

        // First call caches the detail
        $res1 = $this->getJson("/api/v1/temples/{$temple->slug}");
        $res1->assertOk()->assertJsonPath('data.short_description', 'Mô tả ban đầu');

        // Update the temple
        $temple->update(['short_description' => 'Mô tả đã được cập nhật mới']);

        // Next call reflects updated data
        $res2 = $this->getJson("/api/v1/temples/{$temple->slug}");
        $res2->assertOk()->assertJsonPath('data.short_description', 'Mô tả đã được cập nhật mới');
    }

    // =========================================================================
    // 6. ADMIN AUTHORIZATION
    // =========================================================================

    public function test_guest_and_regular_user_cannot_access_filament_admin(): void
    {
        $this->get('/admin/temples')->assertRedirect('/admin/login');

        $regularUser = User::factory()->create(['role' => 'user']);
        $this->actingAs($regularUser)
            ->get('/admin/temples')
            ->assertForbidden();
    }

    public function test_admin_user_can_access_filament_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get('/admin/temples')
            ->assertOk();
    }
}
