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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempleDetailApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createPublishedTemple(array $attributes = []): Temple
    {
        return Temple::create(array_merge([
            'name' => 'Chùa Giác Lâm',
            'place_type' => TempleType::Temple,
            'province_name' => 'Thành phố Hồ Chí Minh',
            'country_code' => 'VN',
            'address_line' => '565 Lạc Long Quân',
            'ward_name' => 'Phường 10',
            'district_name' => 'Quận Tân Bình',
            'publication_status' => TemplePublicationStatus::Published,
            'published_at' => now()->subDay(),
            'verification_status' => TempleVerificationStatus::Verified,
            'public_access_status' => TemplePublicAccessStatus::PubliclyAccessible,
        ], $attributes));
    }

    public function test_guest_can_view_published_temple_detail_by_slug(): void
    {
        $temple = $this->createPublishedTemple([
            'name' => 'Chùa Hoằng Pháp',
            'alternative_names' => ['Tổ đình Hoằng Pháp', 'Hoang Phap Pagoda'],
            'short_description' => 'Ngôi chùa cổ kính tại Hóc Môn.',
            'description' => 'Lịch sử thành lập và phát triển các khóa tu Phật thất.',
            'public_phone' => '02837130002',
            'public_email' => 'lienhe@hoangphap.org',
            'official_website' => 'https://chuahoangphap.com.vn',
            'latitude' => 10.8872,
            'longitude' => 106.5912,
            'coordinate_accuracy' => TempleCoordinateAccuracy::ExactVerified,
            'last_verified_at' => now()->subDays(5),
            'visit_notes' => 'Mở cửa tiếp đón khách thập phương chiêm bái từ 06:00 đến 18:00 hàng ngày.',
            'visit_notes_verified_at' => now()->subDays(5),
        ]);

        $res = $this->getJson("/api/v1/temples/{$temple->slug}");

        $res->assertOk()
            ->assertJsonPath('data.slug', $temple->slug)
            ->assertJsonPath('data.name', 'Chùa Hoằng Pháp')
            ->assertJsonPath('data.place_type', 'temple')
            ->assertJsonPath('data.place_type_label', 'Chùa')
            ->assertJsonPath('data.short_description', 'Ngôi chùa cổ kính tại Hóc Môn.')
            ->assertJsonPath('data.description', 'Lịch sử thành lập và phát triển các khóa tu Phật thất.')
            ->assertJsonPath('data.contacts.phone', '02837130002')
            ->assertJsonPath('data.contacts.email', 'lienhe@hoangphap.org')
            ->assertJsonPath('data.contacts.website', 'https://chuahoangphap.com.vn')
            ->assertJsonPath('data.visit_information.access_status', 'publicly_accessible')
            ->assertJsonPath('data.visit_information.notes', 'Mở cửa tiếp đón khách thập phương chiêm bái từ 06:00 đến 18:00 hàng ngày.')
            ->assertJsonPath('data.coordinates.latitude', 10.8872)
            ->assertJsonPath('data.coordinates.longitude', 106.5912);
    }

    public function test_invalid_slug_returns_404(): void
    {
        $res = $this->getJson('/api/v1/temples/chua-khong-ton-tai-xyz');
        $res->assertNotFound();
    }

    public function test_draft_or_unpublished_temple_returns_404(): void
    {
        $draft = Temple::create([
            'name' => 'Chùa Đang Soạn Thảo',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::Draft,
        ]);

        $inReview = Temple::create([
            'name' => 'Chùa Đang Duyệt',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::InReview,
        ]);

        $archived = Temple::create([
            'name' => 'Chùa Lưu Trữ',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::Archived,
        ]);

        $future = Temple::create([
            'name' => 'Chùa Tương Lai',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::Published,
            'published_at' => now()->addWeek(),
        ]);

        $this->getJson("/api/v1/temples/{$draft->slug}")->assertNotFound();
        $this->getJson("/api/v1/temples/{$inReview->slug}")->assertNotFound();
        $this->getJson("/api/v1/temples/{$archived->slug}")->assertNotFound();
        $this->getJson("/api/v1/temples/{$future->slug}")->assertNotFound();
    }

    public function test_deleted_temple_returns_404(): void
    {
        $temple = $this->createPublishedTemple(['name' => 'Chùa Sẽ Xóa']);
        $slug = $temple->slug;

        $this->getJson("/api/v1/temples/{$slug}")->assertOk();

        $temple->delete();

        $this->getJson("/api/v1/temples/{$slug}")->assertNotFound();
    }

    public function test_coordinates_hidden_when_unverified_or_approximate(): void
    {
        $temple = $this->createPublishedTemple([
            'name' => 'Chùa Tọa Độ Xấp Xỉ',
            'latitude' => 10.1234,
            'longitude' => 106.1234,
            'coordinate_accuracy' => TempleCoordinateAccuracy::Approximate,
        ]);

        $res = $this->getJson("/api/v1/temples/{$temple->slug}");
        $res->assertOk()->assertJsonPath('data.coordinates', null);
    }

    public function test_coordinates_hidden_when_access_status_is_restricted(): void
    {
        $temple = $this->createPublishedTemple([
            'name' => 'Chùa Khu Vực Cấm',
            'latitude' => 10.1234,
            'longitude' => 106.1234,
            'coordinate_accuracy' => TempleCoordinateAccuracy::ExactVerified,
            'public_access_status' => TemplePublicAccessStatus::Restricted,
        ]);

        $res = $this->getJson("/api/v1/temples/{$temple->slug}");
        $res->assertOk()->assertJsonPath('data.coordinates', null);
    }

    public function test_missing_contacts_return_null(): void
    {
        $temple = $this->createPublishedTemple([
            'name' => 'Chùa Không Có Liên Hệ',
            'public_phone' => null,
            'public_email' => null,
            'official_website' => null,
        ]);

        $res = $this->getJson("/api/v1/temples/{$temple->slug}");
        $res->assertOk()
            ->assertJsonPath('data.contacts.phone', null)
            ->assertJsonPath('data.contacts.email', null)
            ->assertJsonPath('data.contacts.website', null);
    }

    public function test_approved_media_and_cover_are_returned_with_ordering(): void
    {
        $temple = $this->createPublishedTemple(['name' => 'Chùa Có Ảnh']);

        TempleMedia::create([
            'temple_id' => $temple->id,
            'file_path' => 'https://storage.ompramani.org/temples/cover.jpg',
            'file_name' => 'cover.jpg',
            'alt_text' => 'Tam quan Chùa',
            'caption' => 'Cổng tam quan buổi sớm',
            'credit' => 'Thích Thiện An',
            'license_info' => 'CC BY-SA 4.0',
            'is_cover' => true,
            'sort_order' => 1,
        ]);

        TempleMedia::create([
            'temple_id' => $temple->id,
            'file_path' => 'https://storage.ompramani.org/temples/gallery1.jpg',
            'file_name' => 'gallery1.jpg',
            'alt_text' => 'Chánh điện trang nghiêm',
            'credit' => 'Ban Thông tin Truyền thông',
            'is_cover' => false,
            'sort_order' => 2,
        ]);

        $res = $this->getJson("/api/v1/temples/{$temple->slug}");
        $res->assertOk()
            ->assertJsonPath('data.cover_image', 'https://storage.ompramani.org/temples/cover.jpg')
            ->assertJsonCount(2, 'data.images')
            ->assertJsonPath('data.images.0.alt_text', 'Tam quan Chùa')
            ->assertJsonPath('data.images.0.credit', 'Thích Thiện An')
            ->assertJsonPath('data.images.0.license_info', 'CC BY-SA 4.0')
            ->assertJsonPath('data.images.0.is_cover', true)
            ->assertJsonPath('data.images.1.alt_text', 'Chánh điện trang nghiêm')
            ->assertJsonPath('data.images.1.is_cover', false);
    }

    public function test_traditions_and_sources_are_returned(): void
    {
        $temple = $this->createPublishedTemple(['name' => 'Chùa Nam Tông']);

        $tradition = Tradition::create([
            'name' => 'Nam tông Kinh (Theravada)',
            'slug' => 'nam-tong-kinh',
            'is_active' => true,
        ]);

        $temple->traditions()->attach($tradition->id, ['is_primary' => true]);

        TempleSource::create([
            'temple_id' => $temple->id,
            'source_title' => 'Cổng thông tin Giáo hội Phật giáo Việt Nam',
            'source_url' => 'https://phatgiao.org.vn/chua-nam-tong',
            'source_type' => TempleSourceType::OfficialWebsite,
            'reference_note' => 'Danh bạ cơ sở tự viện toàn quốc tập 2',
            'checked_at' => now()->subMonth(),
        ]);

        $res = $this->getJson("/api/v1/temples/{$temple->slug}");
        $res->assertOk()
            ->assertJsonCount(1, 'data.traditions')
            ->assertJsonPath('data.traditions.0.name', 'Nam tông Kinh (Theravada)')
            ->assertJsonPath('data.traditions.0.is_primary', true)
            ->assertJsonCount(1, 'data.sources')
            ->assertJsonPath('data.sources.0.title', 'Cổng thông tin Giáo hội Phật giáo Việt Nam')
            ->assertJsonPath('data.sources.0.reference', 'Danh bạ cơ sở tự viện toàn quốc tập 2');
    }

    public function test_visit_information_returns_linked_source(): void
    {
        $temple = $this->createPublishedTemple(['name' => 'Chùa Có Hướng Dẫn Thăm Viếng']);

        $source = TempleSource::create([
            'temple_id' => $temple->id,
            'source_title' => 'Thông bạch Ban Trị Sự',
            'source_url' => 'https://btrts.vn/thong-bach',
            'source_type' => TempleSourceType::OfficialWebsite,
        ]);

        $temple->update([
            'visit_notes' => 'Trang phục trang nghiêm, không mặc áo sát nách hay váy ngắn khi vào chánh điện.',
            'visit_notes_source_id' => $source->id,
            'visit_notes_verified_at' => now(),
        ]);

        $res = $this->getJson("/api/v1/temples/{$temple->slug}");
        $res->assertOk()
            ->assertJsonPath('data.visit_information.notes', 'Trang phục trang nghiêm, không mặc áo sát nách hay váy ngắn khi vào chánh điện.')
            ->assertJsonPath('data.visit_information.source.title', 'Thông bạch Ban Trị Sự')
            ->assertJsonPath('data.visit_information.source.url', 'https://btrts.vn/thong-bach');
    }

    public function test_updating_temple_clears_detail_cache(): void
    {
        $temple = $this->createPublishedTemple(['name' => 'Chùa Cache Ban Đầu']);
        $slug = $temple->slug;

        $res1 = $this->getJson("/api/v1/temples/{$slug}");
        $res1->assertOk()->assertJsonPath('data.name', 'Chùa Cache Ban Đầu');

        // Update name
        $temple->name = 'Chùa Sau Khi Cập Nhật';
        $temple->save();

        $res2 = $this->getJson("/api/v1/temples/{$slug}");
        $res2->assertOk()->assertJsonPath('data.name', 'Chùa Sau Khi Cập Nhật');
    }

    public function test_get_detail_is_idempotent_and_performs_no_db_writes(): void
    {
        $temple = $this->createPublishedTemple();
        $slug = $temple->slug;

        $countBefore = Temple::count();
        $this->getJson("/api/v1/temples/{$slug}")->assertOk();
        $countAfter = Temple::count();

        $this->assertSame($countBefore, $countAfter);
    }
}
