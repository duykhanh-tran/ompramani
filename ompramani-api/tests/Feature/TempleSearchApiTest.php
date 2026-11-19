<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TempleCoordinateAccuracy;
use App\Enums\TemplePublicationStatus;
use App\Enums\TempleType;
use App\Enums\TempleVerificationStatus;
use App\Models\Temple;
use App\Models\Tradition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempleSearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createPublishedTemple(array $attributes = []): Temple
    {
        return Temple::create(array_merge([
            'name' => 'Chùa Hoằng Pháp',
            'place_type' => TempleType::Temple,
            'province_name' => 'TP. Hồ Chí Minh',
            'country_code' => 'VN',
            'publication_status' => TemplePublicationStatus::Published,
            'published_at' => now()->subDay(),
            'verification_status' => TempleVerificationStatus::Verified,
        ], $attributes));
    }

    // =========================================================================
    // 1. VIETNAMESE ACCENT-INSENSITIVE SEARCH
    // =========================================================================

    public function test_accent_insensitive_search_matches_vietnamese_characters(): void
    {
        $this->createPublishedTemple(['name' => 'Chùa Giác Lâm']);
        $this->createPublishedTemple(['name' => 'Thiền Viện Trúc Lâm Đà Lạt', 'place_type' => TempleType::MeditationMonastery]);
        $this->createPublishedTemple(['name' => 'Tịnh Xá Trung Tâm', 'place_type' => TempleType::Vihara]);

        // "chua" finds "Chùa Giác Lâm"
        $resChua = $this->getJson('/api/v1/temples?q=chua');
        $resChua->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chùa Giác Lâm');

        // "thien vien" finds "Thiền Viện Trúc Lâm Đà Lạt"
        $resThien = $this->getJson('/api/v1/temples?q=thien+vien');
        $resThien->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Thiền Viện Trúc Lâm Đà Lạt');

        // "tinh xa" finds "Tịnh Xá Trung Tâm"
        $resTinhXa = $this->getJson('/api/v1/temples?q=tinh+xa');
        $resTinhXa->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Tịnh Xá Trung Tâm');
    }

    public function test_search_case_and_d_normalization(): void
    {
        $this->createPublishedTemple(['name' => 'Chùa Đạo Nguyên', 'province_name' => 'Quảng Nam']);

        // Lowercase "dao nguyen" matches "Đạo Nguyên"
        $resLower = $this->getJson('/api/v1/temples?q=dao+nguyen');
        $resLower->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chùa Đạo Nguyên');

        // Uppercase "ĐẠO NGUYÊN" matches
        $resUpper = $this->getJson('/api/v1/temples?q='.urlencode('ĐẠO NGUYÊN'));
        $resUpper->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chùa Đạo Nguyên');
    }

    public function test_search_matches_alternative_names(): void
    {
        $this->createPublishedTemple([
            'name' => 'Chùa Phật Tích',
            'alternative_names' => ['Vạn Phúc Tự', 'Chùa Lạng'],
        ]);

        $res = $this->getJson('/api/v1/temples?q=van+phuc');
        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chùa Phật Tích');
    }

    public function test_official_name_not_altered_by_normalization(): void
    {
        $temple = $this->createPublishedTemple([
            'name' => 'Chùa Bút Tháp (Ninh Phúc Tự)',
        ]);

        $fresh = $temple->fresh();
        $this->assertSame('Chùa Bút Tháp (Ninh Phúc Tự)', $fresh->name);
        $this->assertSame('chua but thap (ninh phuc tu)', $fresh->normalized_name);
    }

    // =========================================================================
    // 2. RELEVANCE & ORDERING
    // =========================================================================

    public function test_exact_name_match_outranks_partial_match(): void
    {
        $this->createPublishedTemple(['name' => 'Chùa Một Cột Thủ Đức']);
        $this->createPublishedTemple(['name' => 'Chùa Một Cột']);
        $this->createPublishedTemple(['name' => 'Đạo Tràng Chùa Một Cột']);

        $res = $this->getJson('/api/v1/temples?q='.urlencode('Chùa Một Cột'));
        $res->assertOk();

        // Exact match "Chùa Một Cột" should be first in relevance ranking
        $res->assertJsonPath('data.0.name', 'Chùa Một Cột');
    }

    public function test_custom_sorting_supported(): void
    {
        $this->createPublishedTemple(['name' => 'Chùa Ba']);
        $this->createPublishedTemple(['name' => 'Chùa An']);

        $resDesc = $this->getJson('/api/v1/temples?sort=name_desc');
        $resDesc->assertOk()
            ->assertJsonPath('data.0.name', 'Chùa Ba')
            ->assertJsonPath('data.1.name', 'Chùa An');
    }

    // =========================================================================
    // 3. FILTERS COMBINATION (AND LOGIC)
    // =========================================================================

    public function test_combined_search_and_filters(): void
    {
        $tradition = Tradition::create(['name' => 'Thiền Tông', 'slug' => 'thien-tong', 'is_active' => true]);

        $target = $this->createPublishedTemple([
            'name' => 'Thiền Viện Trúc Lâm Tây Thiên',
            'place_type' => TempleType::MeditationMonastery,
            'province_name' => 'Vĩnh Phúc',
            'country_code' => 'VN',
        ]);
        $target->traditions()->attach($tradition->id);

        $other1 = $this->createPublishedTemple([
            'name' => 'Thiền Viện Trúc Lâm Đà Lạt',
            'place_type' => TempleType::MeditationMonastery,
            'province_name' => 'Lâm Đồng',
        ]);
        $other1->traditions()->attach($tradition->id);

        $other2 = $this->createPublishedTemple([
            'name' => 'Chùa Tây Thiên',
            'place_type' => TempleType::Temple,
            'province_name' => 'Vĩnh Phúc',
        ]);

        // Search q='tay thien' + type=meditation_monastery + province=Vĩnh Phúc + tradition=thien-tong
        $url = '/api/v1/temples?q=tay+thien&type=meditation_monastery&province='.urlencode('Vĩnh Phúc').'&tradition=thien-tong';
        $res = $this->getJson($url);

        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $target->slug);
    }

    // =========================================================================
    // 4. FACETS / FILTERS METADATA API
    // =========================================================================

    public function test_filters_endpoint_returns_facets_from_published_data(): void
    {
        $this->createPublishedTemple([
            'name' => 'Chùa Hà Nội',
            'province_name' => 'Hà Nội',
            'country_code' => 'VN',
        ]);
        $this->createPublishedTemple([
            'name' => 'Chùa Đà Nẵng',
            'province_name' => 'Đà Nẵng',
            'country_code' => 'VN',
        ]);

        // Draft temple in different province should not appear in facets
        Temple::create([
            'name' => 'Chùa Nháp',
            'place_type' => TempleType::Temple,
            'province_name' => 'Cà Mau',
            'publication_status' => TemplePublicationStatus::Draft,
        ]);

        $res = $this->getJson('/api/v1/temples/filters');
        $res->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'types',
                    'provinces',
                    'countries',
                    'traditions',
                ],
            ]);

        $provinces = $res->json('data.provinces');
        $this->assertContains('Hà Nội', $provinces);
        $this->assertContains('Đà Nẵng', $provinces);
        $this->assertNotContains('Cà Mau', $provinces);
    }

    // =========================================================================
    // 5. VALIDATION & SECURITY
    // =========================================================================

    public function test_short_query_rejected(): void
    {
        $res = $this->getJson('/api/v1/temples?q=a');
        $res->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    public function test_large_query_rejected(): void
    {
        $large = str_repeat('a', 105);
        $res = $this->getJson('/api/v1/temples?q='.$large);
        $res->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    public function test_unsupported_sort_rejected(): void
    {
        $res = $this->getJson('/api/v1/temples?sort=most_sacred');
        $res->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_oversized_per_page_rejected(): void
    {
        $res = $this->getJson('/api/v1/temples?per_page=99');
        $res->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_sql_injection_attempt_is_safe(): void
    {
        $this->createPublishedTemple(['name' => 'Chùa An Toàn']);

        $malicious = "' OR '1'='1";
        $res = $this->getJson('/api/v1/temples?q='.urlencode($malicious));

        // Returns 200 with 0 results, no SQL error
        $res->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_wildcard_characters_escaped(): void
    {
        $this->createPublishedTemple(['name' => 'Chùa Thật']);

        // Query with "%" should not match everything like a SQL wildcard
        $res = $this->getJson('/api/v1/temples?q=%25%25');
        $res->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_draft_and_archived_never_appear_in_search(): void
    {
        Temple::create([
            'name' => 'Chùa Nháp Bí Mật',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::Draft,
        ]);

        Temple::create([
            'name' => 'Chùa Đã Lưu Trữ',
            'place_type' => TempleType::Temple,
            'publication_status' => TemplePublicationStatus::Archived,
        ]);

        $res = $this->getJson('/api/v1/temples?q=bi+mat');
        $res->assertOk()->assertJsonCount(0, 'data');

        $resArchived = $this->getJson('/api/v1/temples?q=luu+tru');
        $resArchived->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_unverified_coordinates_not_leaked_in_search_results(): void
    {
        $this->createPublishedTemple([
            'name' => 'Chùa Có Tọa Độ Chưa Duyệt',
            'latitude' => 10.5,
            'longitude' => 106.5,
            'coordinate_accuracy' => TempleCoordinateAccuracy::Approximate,
            'verification_status' => TempleVerificationStatus::PendingReview,
        ]);

        $res = $this->getJson('/api/v1/temples?q=chua+co+toa+do');
        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.has_verified_coordinates', false);
    }
}
