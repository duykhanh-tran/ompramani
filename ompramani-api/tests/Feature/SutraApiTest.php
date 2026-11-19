<?php

namespace Tests\Feature;

use App\Models\Sutra;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\Tradition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SutraApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Lấy danh sách hệ phái thành công.
     */
    public function test_can_get_traditions_list_successfully(): void
    {
        $bacTruyen = Tradition::factory()->create([
            'name' => 'Bắc Truyền',
            'slug' => 'bac-truyen',
            'is_active' => true,
        ]);

        $namTruyen = Tradition::factory()->create([
            'name' => 'Nam Truyền',
            'slug' => 'nam-truyen',
            'is_active' => true,
        ]);

        $inactive = Tradition::factory()->inactive()->create([
            'name' => 'Ẩn Truyền',
            'slug' => 'an-truyen',
        ]);

        $response = $this->getJson('/api/v1/traditions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'is_active',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $slugs = collect($data)->pluck('slug')->all();
        $this->assertContains('bac-truyen', $slugs);
        $this->assertContains('nam-truyen', $slugs);
        $this->assertNotContains('an-truyen', $slugs);
    }

    /**
     * 2. Lấy danh sách kinh đã xuất bản.
     */
    public function test_can_get_published_sutras_list(): void
    {
        $tradition = Tradition::factory()->create();
        Sutra::factory()->count(3)->create([
            'tradition_id' => $tradition->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/sutras');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'original_title',
                        'short_description',
                        'thumbnail',
                        'tradition' => [
                            'id',
                            'name',
                            'slug',
                            'description',
                            'is_active',
                        ],
                    ],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * 3. Kinh draft không xuất hiện.
     */
    public function test_draft_and_future_published_sutras_do_not_appear(): void
    {
        $tradition = Tradition::factory()->create();

        $published = Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Đã Xuất Bản',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $draft = Sutra::factory()->draft()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Bản Nháp',
        ]);

        $future = Sutra::factory()->futurePublished()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Xuất Bản Tương Lai',
        ]);

        $response = $this->getJson('/api/v1/sutras');

        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Kinh Đã Xuất Bản', $titles);
        $this->assertNotContains('Kinh Bản Nháp', $titles);
        $this->assertNotContains('Kinh Xuất Bản Tương Lai', $titles);
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * 4. Lọc theo Bắc Truyền.
     */
    public function test_can_filter_sutras_by_bac_truyen(): void
    {
        $bacTruyen = Tradition::factory()->create(['name' => 'Bắc Truyền', 'slug' => 'bac-truyen']);
        $namTruyen = Tradition::factory()->create(['name' => 'Nam Truyền', 'slug' => 'nam-truyen']);

        $sutraBac = Sutra::factory()->create([
            'tradition_id' => $bacTruyen->id,
            'title' => 'Kinh Kim Cương',
            'status' => 'published',
        ]);

        $sutraNam = Sutra::factory()->create([
            'tradition_id' => $namTruyen->id,
            'title' => 'Kinh Pháp Cú',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/sutras?tradition=bac-truyen');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Kinh Kim Cương', $data[0]['title']);
        $this->assertSame('bac-truyen', $data[0]['tradition']['slug']);
    }

    /**
     * 5. Lọc theo Nam Truyền.
     */
    public function test_can_filter_sutras_by_nam_truyen(): void
    {
        $bacTruyen = Tradition::factory()->create(['name' => 'Bắc Truyền', 'slug' => 'bac-truyen']);
        $namTruyen = Tradition::factory()->create(['name' => 'Nam Truyền', 'slug' => 'nam-truyen']);

        Sutra::factory()->create([
            'tradition_id' => $bacTruyen->id,
            'title' => 'Kinh Kim Cương',
            'status' => 'published',
        ]);

        Sutra::factory()->create([
            'tradition_id' => $namTruyen->id,
            'title' => 'Kinh Pháp Cú',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/sutras?tradition=nam-truyen');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Kinh Pháp Cú', $data[0]['title']);
        $this->assertSame('nam-truyen', $data[0]['tradition']['slug']);
    }

    /**
     * 6. Tìm kiếm kinh theo tên.
     */
    public function test_can_search_sutras_by_title(): void
    {
        $tradition = Tradition::factory()->create();

        Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh A Di Đà',
            'slug' => 'kinh-a-di-da',
            'status' => 'published',
        ]);

        Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Pháp Cú',
            'slug' => 'kinh-phap-cu',
            'status' => 'published',
        ]);

        // Search with hyphenated slug style query
        $res1 = $this->getJson('/api/v1/sutras?search=a-di-da');
        $res1->assertStatus(200);
        $this->assertCount(1, $res1->json('data'));
        $this->assertSame('Kinh A Di Đà', $res1->json('data.0.title'));

        // Search with Vietnamese keyword
        $res2 = $this->getJson('/api/v1/sutras?search=A Di Đà');
        $res2->assertStatus(200);
        $this->assertCount(1, $res2->json('data'));
        $this->assertSame('Kinh A Di Đà', $res2->json('data.0.title'));
    }

    /**
     * 7. Xem chi tiết kinh bằng slug.
     */
    public function test_can_view_sutra_details_by_slug(): void
    {
        $tradition = Tradition::factory()->create(['name' => 'Bắc Truyền', 'slug' => 'bac-truyen']);
        $sutra = Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Diệu Pháp Liên Hoa',
            'slug' => 'kinh-dieu-phap-lien-hoa',
            'original_title' => 'Saddharma Pundarika Sutra',
            'short_description' => 'Một trong những bộ kinh Đại thừa quan trọng.',
            'thumbnail' => 'https://example.com/lotus.jpg',
            'status' => 'published',
        ]);

        SutraVersion::factory()->create([
            'sutra_id' => $sutra->id,
            'name' => 'Bản dịch Cưu Ma La Thập',
            'status' => 'published',
            'is_default' => true,
        ]);

        $response = $this->getJson('/api/v1/sutras/kinh-dieu-phap-lien-hoa');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'original_title',
                    'short_description',
                    'thumbnail',
                    'tradition' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'is_active',
                    ],
                    'versions' => [
                        '*' => [
                            'id',
                            'name',
                            'translator',
                            'language',
                            'publisher',
                            'publication_year',
                            'source',
                            'notes',
                            'is_default',
                            'status',
                        ],
                    ],
                ],
            ]);

        $this->assertSame('Kinh Diệu Pháp Liên Hoa', $response->json('data.title'));
        $this->assertSame('kinh-dieu-phap-lien-hoa', $response->json('data.slug'));
        $this->assertCount(1, $response->json('data.versions'));
    }

    /**
     * 8. Truy cập slug không tồn tại trả 404.
     */
    public function test_non_existent_or_draft_slug_returns_404(): void
    {
        // Non-existent slug
        $response = $this->getJson('/api/v1/sutras/khong-ton-tai');
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Không tìm thấy nội dung.',
            ]);

        // Existing slug but draft status
        $draft = Sutra::factory()->draft()->create([
            'slug' => 'kinh-chua-xuat-ban',
        ]);

        $responseDraft = $this->getJson('/api/v1/sutras/kinh-chua-xuat-ban');
        $responseDraft->assertStatus(404)
            ->assertJson([
                'message' => 'Không tìm thấy nội dung.',
            ]);
    }

    /**
     * 9. Chỉ hiển thị phiên bản đã xuất bản.
     */
    public function test_only_published_versions_are_displayed(): void
    {
        $sutra = Sutra::factory()->create([
            'slug' => 'kinh-a-di-da',
            'status' => 'published',
        ]);

        $publishedVersion = SutraVersion::factory()->published()->create([
            'sutra_id' => $sutra->id,
            'name' => 'Bản dịch chính thức',
            'is_default' => true,
        ]);

        $draftVersion = SutraVersion::factory()->draft()->create([
            'sutra_id' => $sutra->id,
            'name' => 'Bản dịch đang soạn thảo',
            'is_default' => false,
        ]);

        // Check in sutra details endpoint
        $resDetails = $this->getJson('/api/v1/sutras/kinh-a-di-da');
        $resDetails->assertStatus(200);
        $versionsInDetails = $resDetails->json('data.versions');
        $this->assertCount(1, $versionsInDetails);
        $this->assertSame('Bản dịch chính thức', $versionsInDetails[0]['name']);

        // Check in versions endpoint
        $resVersions = $this->getJson('/api/v1/sutras/kinh-a-di-da/versions');
        $resVersions->assertStatus(200);
        $versionsList = $resVersions->json('data');
        $this->assertCount(1, $versionsList);
        $this->assertSame('Bản dịch chính thức', $versionsList[0]['name']);
    }

    /**
     * 10. Lấy mục lục đúng thứ tự.
     */
    public function test_can_get_table_of_contents_in_order_without_content(): void
    {
        $sutra = Sutra::factory()->create(['status' => 'published']);
        $version = SutraVersion::factory()->published()->create(['sutra_id' => $sutra->id]);

        $chapter2 = SutraSection::factory()->create([
            'sutra_version_id' => $version->id,
            'parent_id' => null,
            'title' => 'Chương 2',
            'slug' => 'chuong-2',
            'position' => 2,
            'content' => 'Nội dung dài hàng ngàn từ của chương 2...',
        ]);

        $chapter1 = SutraSection::factory()->create([
            'sutra_version_id' => $version->id,
            'parent_id' => null,
            'title' => 'Chương 1',
            'slug' => 'chuong-1',
            'position' => 1,
            'content' => 'Nội dung dài hàng ngàn từ của chương 1...',
        ]);

        $sub2 = SutraSection::factory()->create([
            'sutra_version_id' => $version->id,
            'parent_id' => $chapter1->id,
            'title' => 'Phần 1.2',
            'slug' => 'phan-1-2',
            'position' => 2,
            'content' => 'Chi tiết phần 1.2...',
        ]);

        $sub1 = SutraSection::factory()->create([
            'sutra_version_id' => $version->id,
            'parent_id' => $chapter1->id,
            'title' => 'Phần 1.1',
            'slug' => 'phan-1-1',
            'position' => 1,
            'content' => 'Chi tiết phần 1.1...',
        ]);

        $response = $this->getJson("/api/v1/sutra-versions/{$version->id}/sections");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'position',
                        'parent_id',
                        'children',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Verify root ordering: Chapter 1 (position 1) first, Chapter 2 (position 2) second
        $this->assertSame('Chương 1', $data[0]['title']);
        $this->assertSame('Chương 2', $data[1]['title']);

        // Verify children ordering: Phần 1.1 (position 1) first, Phần 1.2 (position 2) second
        $children = $data[0]['children'];
        $this->assertCount(2, $children);
        $this->assertSame('Phần 1.1', $children[0]['title']);
        $this->assertSame('Phần 1.2', $children[1]['title']);

        // Verify content field is excluded
        $this->assertArrayNotHasKey('content', $data[0]);
        $this->assertArrayNotHasKey('content', $children[0]);
    }

    /**
     * 11. Lấy nội dung section thành công.
     */
    public function test_can_get_section_content_successfully(): void
    {
        $tradition = Tradition::factory()->create();
        $sutra = Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Bát Nhã Ba La Mật Đa',
            'slug' => 'kinh-bat-nha',
            'status' => 'published',
        ]);

        $version = SutraVersion::factory()->published()->create([
            'sutra_id' => $sutra->id,
            'name' => 'Bản dịch Pháp Sư Huyền Trang',
        ]);

        $section = SutraSection::factory()->create([
            'sutra_version_id' => $version->id,
            'parent_id' => null,
            'title' => 'Tâm Kinh Quán Tự Tại',
            'slug' => 'tam-kinh-quan-tu-tai',
            'content' => 'Quán Tự Tại Bồ Tát hành thâm Bát Nhã Ba La Mật Đa thời, chiếu kiến ngũ uẩn giai không...',
            'position' => 1,
            'source_page_start' => 1,
            'source_page_end' => 3,
        ]);

        $response = $this->getJson("/api/v1/sutra-sections/{$section->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'content',
                    'parent_id',
                    'position',
                    'source_page_start',
                    'source_page_end',
                    'version' => [
                        'id',
                        'name',
                        'translator',
                        'language',
                    ],
                    'sutra' => [
                        'id',
                        'title',
                        'slug',
                    ],
                ],
            ]);

        $this->assertSame('Tâm Kinh Quán Tự Tại', $response->json('data.title'));
        $this->assertStringContainsString('Quán Tự Tại Bồ Tát', $response->json('data.content'));
        $this->assertSame($version->id, $response->json('data.version.id'));
        $this->assertSame($sutra->id, $response->json('data.sutra.id'));
    }

    /**
     * 12. Không truy cập được section thuộc phiên bản draft.
     */
    public function test_cannot_access_section_belonging_to_draft_version_or_draft_sutra(): void
    {
        // Case 1: Version is draft
        $sutraPublished = Sutra::factory()->create(['status' => 'published']);
        $draftVersion = SutraVersion::factory()->draft()->create(['sutra_id' => $sutraPublished->id]);
        $sectionInDraftVersion = SutraSection::factory()->create(['sutra_version_id' => $draftVersion->id]);

        $res1 = $this->getJson("/api/v1/sutra-sections/{$sectionInDraftVersion->id}");
        $res1->assertStatus(404)
            ->assertJson([
                'message' => 'Không tìm thấy nội dung.',
            ]);

        // Case 2: Sutra is draft
        $draftSutra = Sutra::factory()->draft()->create();
        $publishedVersion = SutraVersion::factory()->published()->create(['sutra_id' => $draftSutra->id]);
        $sectionInDraftSutra = SutraSection::factory()->create(['sutra_version_id' => $publishedVersion->id]);

        $res2 = $this->getJson("/api/v1/sutra-sections/{$sectionInDraftSutra->id}");
        $res2->assertStatus(404)
            ->assertJson([
                'message' => 'Không tìm thấy nội dung.',
            ]);
    }

    /**
     * 13. Query per_page vượt giới hạn bị từ chối.
     */
    public function test_per_page_exceeding_limit_is_rejected_with_422(): void
    {
        $response = $this->getJson('/api/v1/sutras?per_page=100');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * 14. API trả JSON UTF-8 tiếng Việt đúng.
     */
    public function test_api_returns_proper_utf8_vietnamese_json(): void
    {
        $tradition = Tradition::factory()->create([
            'name' => 'Bắc Truyền',
            'slug' => 'bac-truyen',
            'description' => 'Hệ phái Phật giáo Phát triển Bắc Tông.',
        ]);

        $sutra = Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Đại Bát Niết Bàn',
            'slug' => 'kinh-dai-bat-niet-ban',
            'original_title' => 'Mahāparinirvāṇa Sūtra',
            'short_description' => 'Lời dạy tối hậu của Đức Thế Tôn trước khi nhập Vô Dư Niết Bàn.',
            'status' => 'published',
        ]);

        $version = SutraVersion::factory()->published()->create([
            'sutra_id' => $sutra->id,
            'name' => 'Bản dịch Trưởng Lão Thích Trí Tịnh',
            'translator' => 'Hòa thượng Thích Trí Tịnh',
        ]);

        $section = SutraSection::factory()->create([
            'sutra_version_id' => $version->id,
            'title' => 'Phẩm Tựa Thứ Nhất',
            'slug' => 'pham-tua-thu-nhat',
            'content' => 'Như thật tôi nghe: Một thuở nọ Đức Phật ở trong rừng Ta-la Song Thọ...',
        ]);

        // Verify Sutra Detail endpoint
        $resSutra = $this->getJson("/api/v1/sutras/{$sutra->slug}");
        $resSutra->assertStatus(200);
        $rawSutraContent = $resSutra->getContent();

        $this->assertStringContainsString('Kinh Đại Bát Niết Bàn', $rawSutraContent);
        $this->assertStringContainsString('Bắc Truyền', $rawSutraContent);
        $this->assertStringContainsString('Bản dịch Trưởng Lão Thích Trí Tịnh', $rawSutraContent);

        // Verify Section Detail endpoint
        $resSection = $this->getJson("/api/v1/sutra-sections/{$section->id}");
        $resSection->assertStatus(200);
        $rawSectionContent = $resSection->getContent();

        $this->assertStringContainsString('Phẩm Tựa Thứ Nhất', $rawSectionContent);
        $this->assertStringContainsString('rừng Ta-la Song Thọ', $rawSectionContent);
    }
}
