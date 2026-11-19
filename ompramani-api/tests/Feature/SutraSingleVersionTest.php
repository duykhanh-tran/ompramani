<?php

namespace Tests\Feature;

use App\Models\Sutra;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\Tradition;
use App\Models\User;
use App\Models\UserFavorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SutraSingleVersionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tradition $tradition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->tradition = Tradition::factory()->create([
            'name' => 'Bắc Truyền',
            'slug' => 'bac-truyen',
            'is_active' => true,
        ]);
    }

    /**
     * 1. Sutra has direct metadata fields (translator, publisher, source, notes).
     */
    public function test_sutra_has_direct_translation_and_source_metadata(): void
    {
        $sutra = Sutra::create([
            'tradition_id' => $this->tradition->id,
            'title' => 'Kinh Lăng Nghiêm',
            'slug' => 'kinh-lang-nghiem',
            'original_title' => 'Surangama Sutra',
            'translator' => 'HT. Thích Duy Lực',
            'language' => 'vi',
            'publisher' => 'NXB Tôn Giáo',
            'publication_year' => '2021',
            'source' => 'Ban Hoằng Pháp Trung Ương GHPGVN',
            'notes' => 'Bản dịch chuẩn mực kèm chú giải',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertDatabaseHas('sutras', [
            'id' => $sutra->id,
            'translator' => 'HT. Thích Duy Lực',
            'language' => 'vi',
            'publisher' => 'NXB Tôn Giáo',
            'source' => 'Ban Hoằng Pháp Trung Ương GHPGVN',
        ]);

        $response = $this->getJson("/api/v1/sutras/{$sutra->slug}");
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Kinh Lăng Nghiêm')
            ->assertJsonPath('data.translator', 'HT. Thích Duy Lực')
            ->assertJsonPath('data.publisher', 'NXB Tôn Giáo')
            ->assertJsonPath('data.language', 'vi')
            ->assertJsonPath('data.source', 'Ban Hoằng Pháp Trung Ương GHPGVN');
    }

    /**
     * 2. Sutra sections can be linked directly to Sutra without requiring version.
     */
    public function test_sutra_sections_link_directly_to_sutra(): void
    {
        $sutra = Sutra::create([
            'tradition_id' => $this->tradition->id,
            'title' => 'Kinh Bát Nhã Ba La Mật Đa',
            'slug' => 'kinh-bat-nha',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $section1 = SutraSection::create([
            'sutra_id' => $sutra->id,
            'title' => 'Phần Nhập Đề',
            'slug' => 'phan-nhap-de',
            'content' => 'Quán Tự Tại Bồ Tát hành thâm Bát nhã Ba la mật đa thời...',
            'position' => 1,
        ]);

        $section2 = SutraSection::create([
            'sutra_id' => $sutra->id,
            'parent_id' => $section1->id,
            'title' => 'Chi Tiết Chân Ngôn',
            'slug' => 'chi-tiet-chan-ngon',
            'content' => 'Yết đế yết đế ba la yết đế...',
            'position' => 2,
        ]);

        $this->assertCount(1, $sutra->sections); // Top level
        $this->assertCount(2, $sutra->allSections); // All levels
        $this->assertSame($sutra->id, $section1->sutra->id);
    }

    /**
     * 3. Direct sections endpoint /api/v1/sutras/{slug}/sections returns section tree.
     */
    public function test_can_fetch_sections_tree_directly_via_sutra_slug(): void
    {
        $sutra = Sutra::create([
            'tradition_id' => $this->tradition->id,
            'title' => 'Kinh Di Giáo',
            'slug' => 'kinh-di-giao',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $parent = SutraSection::create([
            'sutra_id' => $sutra->id,
            'title' => 'Chương 1: Lời Dạy Cuối Cùng',
            'slug' => 'chuong-1',
            'position' => 1,
            'content' => 'Đức Thế Tôn sắp nhập Niết bàn...',
        ]);

        $child = SutraSection::create([
            'sutra_id' => $sutra->id,
            'parent_id' => $parent->id,
            'title' => 'Tiết 1.1: Giữ Gìn Giới Luật',
            'slug' => 'tiet-1-1',
            'position' => 1,
            'content' => 'Các Tỳ kheo sau khi Như Lai diệt độ...',
        ]);

        $response = $this->getJson("/api/v1/sutras/{$sutra->slug}/sections");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sutra_id',
                        'title',
                        'slug',
                        'position',
                        'children' => [
                            '*' => [
                                'id',
                                'parent_id',
                                'title',
                                'slug',
                            ],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($parent->id, $data[0]['id']);
        $this->assertCount(1, $data[0]['children']);
        $this->assertSame($child->id, $data[0]['children'][0]['id']);
    }

    /**
     * 4. Single-version reading progress upsert without sutra_version_id.
     */
    public function test_user_can_save_reading_progress_without_sutra_version_id(): void
    {
        Sanctum::actingAs($this->user);

        $sutra = Sutra::create([
            'tradition_id' => $this->tradition->id,
            'title' => 'Kinh Phổ Môn',
            'slug' => 'kinh-pho-mon',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $section = SutraSection::create([
            'sutra_id' => $sutra->id,
            'title' => 'Phẩm Phổ Môn',
            'slug' => 'pham-pho-mon',
            'position' => 1,
            'content' => 'Lúc bấy giờ Vô Tận Ý Bồ Tát...',
        ]);

        // Save progress without sutra_version_id
        $response = $this->putJson("/api/v1/me/sutras/{$sutra->slug}/reading-progress", [
            'sutra_section_id' => $section->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.sutra.id', $sutra->id)
            ->assertJsonPath('data.section.id', $section->id);

        $this->assertDatabaseHas('sutra_reading_progress', [
            'user_id' => $this->user->id,
            'sutra_id' => $sutra->id,
            'sutra_section_id' => $section->id,
        ]);
    }

    /**
     * 5. Single-version bookmark creation without sutra_version_id.
     */
    public function test_user_can_bookmark_section_without_sutra_version_id(): void
    {
        Sanctum::actingAs($this->user);

        $sutra = Sutra::create([
            'tradition_id' => $this->tradition->id,
            'title' => 'Kinh Vô Lượng Thọ',
            'slug' => 'kinh-vo-luong-tho',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $section = SutraSection::create([
            'sutra_id' => $sutra->id,
            'title' => 'Đại Nguyện Thứ Mười Tám',
            'slug' => 'dai-nguyen-18',
            'position' => 1,
            'content' => 'Giả sử khi tôi thành Phật...',
        ]);

        // Bookmark section without sutra_version_id
        $response = $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'section',
            'sutra_id' => $sutra->id,
            'sutra_section_id' => $section->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'section')
            ->assertJsonPath('data.sutra.id', $sutra->id)
            ->assertJsonPath('data.section.id', $section->id);

        $this->assertDatabaseHas('sutra_bookmarks', [
            'user_id' => $this->user->id,
            'type' => 'section',
            'sutra_id' => $sutra->id,
            'sutra_section_id' => $section->id,
        ]);
    }

    /**
     * 6. UserFavorite points directly to Sutra and remains functional.
     */
    public function test_favorites_point_directly_to_sutra(): void
    {
        Sanctum::actingAs($this->user);

        $sutra = Sutra::create([
            'tradition_id' => $this->tradition->id,
            'title' => 'Kinh Hoa Nghiêm',
            'slug' => 'kinh-hoa-nghiem',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $favorite = UserFavorite::create([
            'user_id' => $this->user->id,
            'favoritable_type' => 'sutra',
            'favoritable_id' => $sutra->id,
        ]);

        $this->assertTrue($favorite->isContentAvailable());
        $this->assertSame('kinh-hoa-nghiem', $favorite->getContentPublicId());
        $this->assertSame('Kinh Hoa Nghiêm', $favorite->getContentTitle());
    }

    /**
     * 7. Section content endpoint works directly with single-version section.
     */
    public function test_can_view_section_content_directly(): void
    {
        $sutra = Sutra::create([
            'tradition_id' => $this->tradition->id,
            'title' => 'Kinh Viên Giác',
            'slug' => 'kinh-vien-giac',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $section = SutraSection::create([
            'sutra_id' => $sutra->id,
            'title' => 'Chương Văn Thù Sư Lợi Bồ Tát',
            'slug' => 'chuong-van-thu',
            'position' => 1,
            'content' => 'Bấy giờ Văn Thù Sư Lợi Pháp Vương Tử...',
        ]);

        $response = $this->getJson("/api/v1/sutra-sections/{$section->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $section->id)
            ->assertJsonPath('data.title', 'Chương Văn Thù Sư Lợi Bồ Tát')
            ->assertJsonPath('data.sutra.id', $sutra->id)
            ->assertJsonPath('data.sutra.slug', 'kinh-vien-giac');
    }

    /**
     * 8. Backward compatibility: existing version endpoints continue to work.
     */
    public function test_existing_version_endpoints_maintain_backward_compatibility(): void
    {
        $sutra = Sutra::create([
            'tradition_id' => $this->tradition->id,
            'title' => 'Kinh Thập Thiện',
            'slug' => 'kinh-thap-thien',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $version = SutraVersion::create([
            'sutra_id' => $sutra->id,
            'name' => 'Bản dịch chính thức',
            'translator' => 'Cư sĩ Hạnh Cơ',
            'language' => 'vi',
            'status' => 'published',
            'is_default' => true,
        ]);

        $section = SutraSection::create([
            'sutra_id' => $sutra->id,
            'sutra_version_id' => $version->id,
            'title' => 'Mười Nghiệp Lành',
            'slug' => 'muoi-nghiep-lanh',
            'position' => 1,
            'content' => 'Phật dạy không sát sinh, không trộm cắp...',
        ]);

        // Versions list endpoint
        $resVersions = $this->getJson("/api/v1/sutras/{$sutra->slug}/versions");
        $resVersions->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Bản dịch chính thức');

        // Version sections endpoint
        $resSections = $this->getJson("/api/v1/sutra-versions/{$version->id}/sections");
        $resSections->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Mười Nghiệp Lành');
    }
}
