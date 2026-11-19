<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TempleCoordinateAccuracy;
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
use InvalidArgumentException;
use Tests\TestCase;

class TempleDirectoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_temple_draft(): void
    {
        $temple = Temple::create([
            'name' => 'Chùa Hoằng Pháp',
            'place_type' => TempleType::Temple,
            'province_name' => 'TP. Hồ Chí Minh',
            'country_code' => 'VN',
        ]);

        $this->assertDatabaseHas('temples', [
            'id' => $temple->id,
            'name' => 'Chùa Hoằng Pháp',
            'publication_status' => TemplePublicationStatus::Draft->value,
            'verification_status' => TempleVerificationStatus::Unverified->value,
        ]);

        $this->assertNotEmpty($temple->public_id);
        $this->assertEquals('chua-hoang-phap', $temple->slug);
    }

    public function test_unicode_vietnamese_name_and_alternative_names_preserved(): void
    {
        $altNames = [
            'Chùa Pháp Hoa 2',
            'Sắc Tứ Hoằng Pháp',
        ];

        $temple = Temple::create([
            'name' => 'Chùa Linh Ứng Bãi Bụt',
            'alternative_names' => $altNames,
            'place_type' => TempleType::Temple,
            'short_description' => 'Ngôi chùa tọa lạc tại bán đảo Sơn Trà.',
            'province_name' => 'Đà Nẵng',
        ]);

        $fresh = $temple->fresh();
        $this->assertSame('Chùa Linh Ứng Bãi Bụt', $fresh->name);
        $this->assertSame($altNames, $fresh->alternative_names);
        $this->assertSame('Ngôi chùa tọa lạc tại bán đảo Sơn Trà.', $fresh->short_description);
    }

    public function test_valid_place_type_enum(): void
    {
        $temple = Temple::create([
            'name' => 'Thiền Viện Trúc Lâm Đà Lạt',
            'place_type' => TempleType::MeditationMonastery,
            'province_name' => 'Lâm Đồng',
        ]);

        $this->assertSame(TempleType::MeditationMonastery, $temple->fresh()->place_type);
        $this->assertSame('Thiền viện', $temple->fresh()->place_type->label());
    }

    public function test_duplicate_name_across_provinces_generates_unique_slugs(): void
    {
        $t1 = Temple::create([
            'name' => 'Chùa Linh Ứng',
            'province_name' => 'Đà Nẵng',
            'place_type' => TempleType::Temple,
        ]);

        $t2 = Temple::create([
            'name' => 'Chùa Linh Ứng',
            'province_name' => 'Hải Phòng',
            'place_type' => TempleType::Temple,
        ]);

        $this->assertNotEquals($t1->slug, $t2->slug);
        $this->assertNotEquals($t1->public_id, $t2->public_id);
        $this->assertEquals('chua-linh-ung', $t1->slug);
        $this->assertStringStartsWith('chua-linh-ung-', $t2->slug);
    }

    public function test_valid_coordinates_pair_accepted(): void
    {
        $temple = Temple::create([
            'name' => 'Chùa Một Cột',
            'place_type' => TempleType::Temple,
            'latitude' => 21.0358,
            'longitude' => 105.8335,
            'coordinate_accuracy' => TempleCoordinateAccuracy::ExactVerified,
            'verification_status' => TempleVerificationStatus::Verified,
        ]);

        $this->assertEquals(21.0358, $temple->fresh()->latitude);
        $this->assertEquals(105.8335, $temple->fresh()->longitude);
        $this->assertTrue($temple->hasPublicCoordinates());
    }

    public function test_single_coordinate_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tọa độ kinh độ và vĩ độ phải cùng có giá trị hoặc cùng để trống.');

        Temple::create([
            'name' => 'Chùa Quán Sứ',
            'place_type' => TempleType::Temple,
            'latitude' => 21.025,
            'longitude' => null,
        ]);
    }

    public function test_latitude_out_of_range_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Vĩ độ (latitude) phải nằm trong khoảng từ -90 đến 90 độ.');

        Temple::create([
            'name' => 'Chùa Test',
            'place_type' => TempleType::Temple,
            'latitude' => 95.0,
            'longitude' => 105.0,
        ]);
    }

    public function test_longitude_out_of_range_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Kinh độ (longitude) phải nằm trong khoảng từ -180 đến 180 độ.');

        Temple::create([
            'name' => 'Chùa Test Longitude',
            'place_type' => TempleType::Temple,
            'latitude' => 10.0,
            'longitude' => 200.0,
        ]);
    }

    public function test_both_coordinates_null_accepted(): void
    {
        $temple = Temple::create([
            'name' => 'Chùa Chưa Có Tọa Độ',
            'place_type' => TempleType::Temple,
            'latitude' => null,
            'longitude' => null,
            'coordinate_accuracy' => TempleCoordinateAccuracy::Unavailable,
        ]);

        $this->assertNull($temple->fresh()->latitude);
        $this->assertNull($temple->fresh()->longitude);
        $this->assertFalse($temple->hasPublicCoordinates());
    }

    public function test_invalid_email_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email công khai không đúng định dạng.');

        Temple::create([
            'name' => 'Chùa Test Email',
            'place_type' => TempleType::Temple,
            'public_email' => 'invalid-email-address',
        ]);
    }

    public function test_invalid_website_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Trang web chính thức phải là một URL hợp lệ');

        Temple::create([
            'name' => 'Chùa Test Website',
            'place_type' => TempleType::Temple,
            'official_website' => 'javascript:alert(1)',
        ]);
    }

    public function test_formatted_address_helper(): void
    {
        $temple = Temple::create([
            'name' => 'Chùa Bái Đính',
            'place_type' => TempleType::Temple,
            'address_line' => 'Xã Gia Sinh',
            'district_name' => 'Huyện Gia Viễn',
            'province_name' => 'Ninh Bình',
            'country_code' => 'VN',
        ]);

        $this->assertSame('Xã Gia Sinh, Huyện Gia Viễn, Ninh Bình, Việt Nam', $temple->formattedAddress());
    }

    public function test_soft_deletes(): void
    {
        $temple = Temple::create([
            'name' => 'Chùa Cổ',
            'place_type' => TempleType::Temple,
        ]);

        $temple->delete();
        $this->assertSoftDeleted('temples', ['id' => $temple->id]);
        $this->assertNull(Temple::find($temple->id));
        $this->assertNotNull(Temple::withTrashed()->find($temple->id));
    }

    public function test_relationships_integrity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $tradition = Tradition::create([
            'name' => 'Bắc Truyền',
            'slug' => 'bac-truyen',
            'is_active' => true,
        ]);

        $temple = Temple::create([
            'name' => 'Chùa Vĩnh Nghiêm',
            'place_type' => TempleType::Temple,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        $temple->traditions()->attach($tradition->id, ['is_primary' => true]);

        $source = TempleSource::create([
            'temple_id' => $temple->id,
            'source_title' => 'Sách Danh Lam Cổ Tự',
            'source_type' => TempleSourceType::BookDocument,
            'checked_at' => now(),
        ]);

        $media = TempleMedia::create([
            'temple_id' => $temple->id,
            'file_path' => 'temples/vinh-nghiem.jpg',
            'alt_text' => 'Cổng chùa Vĩnh Nghiêm',
            'is_cover' => true,
            'sort_order' => 1,
        ]);

        $fresh = $temple->fresh(['traditions', 'sources', 'media', 'coverMedia', 'reviewer']);

        $this->assertCount(1, $fresh->traditions);
        $this->assertTrue((bool) $fresh->traditions->first()->pivot->is_primary);
        $this->assertCount(1, $fresh->sources);
        $this->assertSame($source->id, $fresh->sources->first()->id);
        $this->assertCount(1, $fresh->media);
        $this->assertSame($media->id, $fresh->coverMedia->id);
        $this->assertSame('temples/vinh-nghiem.jpg', $fresh->getCoverImageUrl());
        $this->assertSame($user->id, $fresh->reviewer->id);
    }
}
