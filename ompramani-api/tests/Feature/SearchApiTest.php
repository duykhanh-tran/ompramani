<?php

namespace Tests\Feature;

use App\Models\Sutra;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\Tradition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected Tradition $bacTruyen;

    protected Tradition $namTruyen;

    protected Sutra $sutraAdida;

    protected Sutra $sutraDiaTang;

    protected Sutra $sutraPhapCu;

    protected Sutra $draftSutra;

    protected Sutra $sutraWithDraftVersion;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Traditions
        $this->bacTruyen = Tradition::factory()->create([
            'name' => 'Bắc Truyền',
            'slug' => 'bac-truyen',
            'is_active' => true,
        ]);

        $this->namTruyen = Tradition::factory()->create([
            'name' => 'Nam Truyền',
            'slug' => 'nam-truyen',
            'is_active' => true,
        ]);

        // 2. Kinh A Di Đà (Published)
        $this->sutraAdida = Sutra::factory()->create([
            'tradition_id' => $this->bacTruyen->id,
            'title' => 'Kinh A Di Đà',
            'slug' => 'kinh-a-di-da',
            'original_title' => 'Amitābha Sūtra',
            'short_description' => 'Bản kinh trọng yếu về cõi Cực Lạc và Đức Phật A Di Đà.',
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);

        $this->sutraAdida->searchAliases()->createMany([
            ['alias' => 'Amitabha', 'normalized_alias' => 'amitabha'],
            ['alias' => 'A Di Da', 'normalized_alias' => 'a di da'],
        ]);

        $versionAdida = SutraVersion::factory()->create([
            'sutra_id' => $this->sutraAdida->id,
            'name' => 'Bản dịch HT Thích Trí Tịnh',
            'status' => 'published',
            'is_default' => true,
        ]);

        SutraSection::factory()->create([
            'sutra_version_id' => $versionAdida->id,
            'title' => 'Chính văn Kinh A Di Đà',
            'slug' => 'chinh-van',
            'content' => 'Từ đây qua phương Tây quá mười muôn ức cõi Phật có thế giới tên là Cực Lạc.',
            'position' => 1,
        ]);

        // 3. Kinh Địa Tạng (Published)
        $this->sutraDiaTang = Sutra::factory()->create([
            'tradition_id' => $this->bacTruyen->id,
            'title' => 'Kinh Địa Tạng',
            'slug' => 'kinh-dia-tang',
            'original_title' => 'Kṣitigarbha Bodhisattva Sūtra',
            'short_description' => 'Địa Tạng Bồ Tát Bổn Nguyện Kinh hiếu kính phụ mẫu.',
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);

        $this->sutraDiaTang->searchAliases()->createMany([
            ['alias' => 'Địa Tạng', 'normalized_alias' => 'dia tang'],
            ['alias' => 'Ksitigarbha', 'normalized_alias' => 'ksitigarbha'],
        ]);

        $versionDiaTang = SutraVersion::factory()->create([
            'sutra_id' => $this->sutraDiaTang->id,
            'name' => 'Bản dịch phổ thông',
            'status' => 'published',
            'is_default' => true,
        ]);

        SutraSection::factory()->create([
            'sutra_version_id' => $versionDiaTang->id,
            'title' => 'Thần thông cung trời Đao Lợi',
            'slug' => 'than-thong-dao-loi',
            'content' => 'Lúc bấy giờ Đức Phật Thích Ca Mâu Ni ở trên cung trời Đao Lợi thuyết pháp cứu độ.',
            'position' => 1,
        ]);

        // 4. Kinh Pháp Cú (Published, Nam Truyền)
        $this->sutraPhapCu = Sutra::factory()->create([
            'tradition_id' => $this->namTruyen->id,
            'title' => 'Kinh Pháp Cú',
            'slug' => 'kinh-phap-cu',
            'original_title' => 'Dhammapada',
            'short_description' => 'Tuyển tập 423 kệ ngôn của Đức Phật.',
            'status' => 'published',
            'published_at' => now()->subDays(2),
        ]);

        $this->sutraPhapCu->searchAliases()->create([
            'alias' => 'Dhammapada',
            'normalized_alias' => 'dhammapada',
        ]);

        $versionPhapCu = SutraVersion::factory()->create([
            'sutra_id' => $this->sutraPhapCu->id,
            'name' => 'Bản dịch HT Thích Minh Châu',
            'status' => 'published',
            'is_default' => true,
        ]);

        SutraSection::factory()->create([
            'sutra_version_id' => $versionPhapCu->id,
            'title' => 'Phẩm Song Yếu',
            'slug' => 'pham-song-yeu',
            'content' => 'Ý dẫn đầu các pháp, ý làm chủ ý tạo, với ý thanh tịnh an lạc bước theo sau.',
            'position' => 1,
        ]);

        // 5. Draft Sutra (Chưa công bố)
        $this->draftSutra = Sutra::factory()->create([
            'tradition_id' => $this->bacTruyen->id,
            'title' => 'Kinh Bản Nháp Tuyệt Mật',
            'slug' => 'kinh-ban-nhap-tuyet-mat',
            'short_description' => 'Bản kinh thử nghiệm nội bộ chưa công bố.',
            'status' => 'draft',
        ]);

        // 6. Published Sutra có một Version Draft chứa nội dung riêng
        $this->sutraWithDraftVersion = Sutra::factory()->create([
            'tradition_id' => $this->bacTruyen->id,
            'title' => 'Kinh Khảo Nghiệm',
            'slug' => 'kinh-khao-nghiem',
            'short_description' => 'Kinh đã công bố nhưng có bản dịch nháp.',
            'status' => 'published',
            'published_at' => now()->subDays(1),
        ]);

        $draftVersion = SutraVersion::factory()->create([
            'sutra_id' => $this->sutraWithDraftVersion->id,
            'name' => 'Bản dịch nháp thử nghiệm',
            'status' => 'draft',
            'is_default' => false,
        ]);

        SutraSection::factory()->create([
            'sutra_version_id' => $draftVersion->id,
            'title' => 'Phần nháp bảo mật',
            'slug' => 'phan-nhap-bao-mat',
            'content' => 'Cụm_từ_tuyệt_mật_chỉ_có_trong_bản_nháp_không_được_tìm_thấy.',
            'position' => 1,
        ]);
    }

    /**
     * 1. Search chính xác title.
     */
    public function test_1_search_exact_title(): void
    {
        $response = $this->getJson('/api/v1/search?q=Kinh A Di Đà');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', fn ($total) => $total >= 1);

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Kinh A Di Đà', $titles);
    }

    /**
     * 2. Search không dấu.
     */
    public function test_2_search_unaccented_vietnamese(): void
    {
        $response = $this->getJson('/api/v1/search?q=kinh a di da');

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Kinh A Di Đà', $titles);
    }

    /**
     * 3. Search lowercase/uppercase.
     */
    public function test_3_search_case_insensitivity(): void
    {
        $responseLower = $this->getJson('/api/v1/search?q=kinh dia tang');
        $responseUpper = $this->getJson('/api/v1/search?q=KINH DIA TANG');

        $responseLower->assertStatus(200);
        $responseUpper->assertStatus(200);

        $titlesLower = collect($responseLower->json('data'))->pluck('title')->all();
        $titlesUpper = collect($responseUpper->json('data'))->pluck('title')->all();

        $this->assertContains('Kinh Địa Tạng', $titlesLower);
        $this->assertContains('Kinh Địa Tạng', $titlesUpper);
    }

    /**
     * 4. Search alias.
     */
    public function test_4_search_alias(): void
    {
        $responseAmitabha = $this->getJson('/api/v1/search?q=Amitabha');
        $responseAmitabha->assertStatus(200);

        $sutra = collect($responseAmitabha->json('data'))->firstWhere('title', 'Kinh A Di Đà');
        $this->assertNotNull($sutra);
        $this->assertContains('alias', $sutra['matched_in']);

        $responseKsitigarbha = $this->getJson('/api/v1/search?q=Ksitigarbha');
        $responseKsitigarbha->assertStatus(200);
        $diaTang = collect($responseKsitigarbha->json('data'))->firstWhere('title', 'Kinh Địa Tạng');
        $this->assertNotNull($diaTang);
    }

    /**
     * 5. Search description.
     */
    public function test_5_search_description(): void
    {
        $response = $this->getJson('/api/v1/search?q=Cực Lạc');

        $response->assertStatus(200);
        $sutra = collect($response->json('data'))->firstWhere('title', 'Kinh A Di Đà');
        $this->assertNotNull($sutra);
        $this->assertContains('description', $sutra['matched_in']);
    }

    /**
     * 6. Search section content.
     */
    public function test_6_search_section_content(): void
    {
        $response = $this->getJson('/api/v1/search?q=mười muôn ức');

        $response->assertStatus(200);
        $sutra = collect($response->json('data'))->firstWhere('title', 'Kinh A Di Đà');
        $this->assertNotNull($sutra);
        $this->assertContains('content', $sutra['matched_in']);
    }

    /**
     * 7. Draft Sutra không xuất hiện.
     */
    public function test_7_draft_sutra_is_excluded(): void
    {
        $response = $this->getJson('/api/v1/search?q=Bản Nháp Tuyệt Mật');

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertNotContains('Kinh Bản Nháp Tuyệt Mật', $titles);
    }

    /**
     * 8. Draft SutraVersion/Section không làm lộ content.
     */
    public function test_8_draft_version_content_is_not_exposed(): void
    {
        $response = $this->getJson('/api/v1/search?q=Cụm_từ_tuyệt_mật_chỉ_có_trong_bản_nháp');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 0);
        $this->assertEmpty($response->json('data'));
    }

    /**
     * 9. Filter Bắc Truyền.
     */
    public function test_9_filter_tradition_bac_truyen(): void
    {
        $response = $this->getJson('/api/v1/search?q=kinh&tradition=bac-truyen');

        $response->assertStatus(200);
        $items = $response->json('data');
        $sutras = collect($items)->where('type', 'sutra');

        $this->assertNotEmpty($sutras);
        foreach ($sutras as $s) {
            $this->assertSame('bac-truyen', $s['tradition']['slug']);
        }
    }

    /**
     * 10. Filter Nam Truyền.
     */
    public function test_10_filter_tradition_nam_truyen(): void
    {
        $response = $this->getJson('/api/v1/search?q=kinh&tradition=nam-truyen');

        $response->assertStatus(200);
        $items = $response->json('data');
        $sutras = collect($items)->where('type', 'sutra');

        $this->assertNotEmpty($sutras);
        foreach ($sutras as $s) {
            $this->assertSame('nam-truyen', $s['tradition']['slug']);
        }
    }

    /**
     * 11. Filter type.
     */
    public function test_11_filter_type(): void
    {
        // Type = sutra
        $resSutra = $this->getJson('/api/v1/search?q=kinh&type=sutra');
        $resSutra->assertStatus(200);
        foreach ($resSutra->json('data') as $item) {
            $this->assertSame('sutra', $item['type']);
        }

        // Type = feature
        $resFeature = $this->getJson('/api/v1/search?q=trang hat&type=feature');
        $resFeature->assertStatus(200);
        foreach ($resFeature->json('data') as $item) {
            $this->assertSame('feature', $item['type']);
        }

        // Type = mantra (not implemented yet, returns empty list honestly)
        $resMantra = $this->getJson('/api/v1/search?q=chu&type=mantra');
        $resMantra->assertStatus(200)
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('data', []);
    }

    /**
     * 12. Pagination.
     */
    public function test_12_pagination(): void
    {
        $resPage1 = $this->getJson('/api/v1/search?q=kinh&type=sutra&page=1&per_page=1');
        $resPage1->assertStatus(200)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 1);

        $this->assertCount(1, $resPage1->json('data'));

        $resPage2 = $this->getJson('/api/v1/search?q=kinh&type=sutra&page=2&per_page=1');
        $resPage2->assertStatus(200)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.per_page', 1);

        $this->assertCount(1, $resPage2->json('data'));

        // Page 1 and Page 2 should not have same record ID
        $this->assertNotSame(
            $resPage1->json('data.0.id'),
            $resPage2->json('data.0.id')
        );
    }

    /**
     * 13. per_page vượt giới hạn bị từ chối.
     */
    public function test_13_per_page_over_limit_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/search?q=kinh&per_page=100000');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    /**
     * 14. Empty query bị validation.
     */
    public function test_14_empty_query_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/search?q=');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    /**
     * 15. Query quá dài bị validation.
     */
    public function test_15_query_too_long_is_rejected(): void
    {
        $longQuery = str_repeat('a', 105);
        $response = $this->getJson("/api/v1/search?q={$longQuery}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    /**
     * 16. Suggestions hoạt động.
     */
    public function test_16_suggestions_work(): void
    {
        $response = $this->getJson('/api/v1/search/suggestions?q=a di');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['type', 'title'],
                ],
            ]);

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Kinh A Di Đà', $titles);
    }

    /**
     * 17. Suggestions không trả draft.
     */
    public function test_17_suggestions_do_not_return_draft(): void
    {
        $response = $this->getJson('/api/v1/search/suggestions?q=tuyệt mật');

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertNotContains('Kinh Bản Nháp Tuyệt Mật', $titles);
    }

    /**
     * 18. Feature Registry trả đúng feature khi tìm “tràng hạt”.
     */
    public function test_18_feature_registry_finds_trang_hat(): void
    {
        $response = $this->getJson('/api/v1/search?q=tràng hạt');

        $response->assertStatus(200);
        $feature = collect($response->json('data'))->firstWhere('type', 'feature');

        $this->assertNotNull($feature);
        $this->assertSame('Tràng hạt', $feature['title']);
        $this->assertSame('/trang-hat', $feature['url']);
    }

    /**
     * 18b. Feature Registry trả đúng feature khi tìm “thiền định”.
     */
    public function test_18b_feature_registry_finds_thien_dinh(): void
    {
        $response = $this->getJson('/api/v1/search?q=thiền định');

        $response->assertStatus(200);
        $feature = collect($response->json('data'))->firstWhere('type', 'feature');

        $this->assertNotNull($feature);
        $this->assertSame('Thiền định', $feature['title']);
        $this->assertSame('/thien', $feature['url']);
    }

    /**
     * 19. SQL injection-like query không gây lỗi/không bypass filter.
     */
    public function test_19_sql_injection_safe(): void
    {
        $maliciousQueries = [
            "' OR '1'='1",
            "'; DROP TABLE sutras; --",
            "1' UNION SELECT 1,2,3 --",
        ];

        foreach ($maliciousQueries as $maliciousQuery) {
            $response = $this->getJson('/api/v1/search?q='.urlencode($maliciousQuery));

            // Must respond with 200 or 422, but never 500 error
            $this->assertContains($response->status(), [200, 422]);

            // Database integrity remains intact
            $this->assertDatabaseHas('sutras', ['id' => $this->sutraAdida->id]);
        }
    }

    /**
     * 20. Search tiếng Việt UTF-8 đúng.
     */
    public function test_20_vietnamese_utf8_accuracy(): void
    {
        $queries = [
            'Địa Tạng Bồ Tát' => 'Kinh Địa Tạng',
            'Ý dẫn đầu các pháp' => 'Kinh Pháp Cú',
            'Cực Lạc' => 'Kinh A Di Đà',
        ];

        foreach ($queries as $query => $expectedTitle) {
            $response = $this->getJson('/api/v1/search?q='.urlencode($query));
            $response->assertStatus(200);

            $titles = collect($response->json('data'))->pluck('title')->all();
            $this->assertContains($expectedTitle, $titles, "Query '{$query}' failed to find '{$expectedTitle}'");
        }
    }
}
