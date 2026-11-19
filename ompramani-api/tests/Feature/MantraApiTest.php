<?php

namespace Tests\Feature;

use App\Filament\Resources\Mantras\MantraResource;
use App\Models\BuddhistFigure;
use App\Models\Mantra;
use App\Models\MantraVersion;
use App\Models\Tradition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MantraApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Admin tạo / quản lý Mantra qua Filament.
     */
    public function test_01_admin_can_crud_mantra(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin);
        $this->assertTrue(MantraResource::canViewAny());
        $this->assertTrue(MantraResource::canCreate());

        $mantra = Mantra::factory()->create([
            'title' => 'Chú Đại Bi',
            'slug' => 'chu-dai-bi',
            'status' => 'published',
        ]);

        $this->assertTrue(MantraResource::canEdit($mantra));
        $this->assertTrue(MantraResource::canDelete($mantra));

        // Regular user blocked
        $this->actingAs($user);
        $this->assertFalse(MantraResource::canViewAny());
        $this->assertFalse(MantraResource::canCreate());
        $this->assertFalse(MantraResource::canEdit($mantra));
        $this->assertFalse(MantraResource::canDelete($mantra));
    }

    /**
     * 2. Mantra liên kết Buddhist Figure.
     */
    public function test_02_mantra_linked_to_buddhist_figure(): void
    {
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Quán Thế Âm Bồ Tát',
            'slug' => 'quan-the-am-bo-tat',
        ]);

        $mantra = Mantra::factory()->published()->create([
            'title' => 'Chú Đại Bi',
            'slug' => 'chu-dai-bi',
            'buddhist_figure_id' => $figure->id,
        ]);

        $response = $this->getJson('/api/v1/mantras/chu-dai-bi');

        $response->assertStatus(200)
            ->assertJsonPath('data.figure.id', $figure->id)
            ->assertJsonPath('data.figure.name', 'Quán Thế Âm Bồ Tát')
            ->assertJsonPath('data.figure.slug', 'quan-the-am-bo-tat');
    }

    /**
     * 3. Tạo nhiều MantraVersion.
     */
    public function test_03_multiple_mantra_versions_created_and_accessible(): void
    {
        $mantra = Mantra::factory()->published()->create(['slug' => 'chu-dai-bi']);

        MantraVersion::factory()->published()->create([
            'mantra_id' => $mantra->id,
            'name' => 'Phạn văn',
            'language' => 'sa',
        ]);

        MantraVersion::factory()->published()->create([
            'mantra_id' => $mantra->id,
            'name' => 'Phiên âm Hán-Việt',
            'language' => 'zh-Latn',
        ]);

        MantraVersion::factory()->published()->create([
            'mantra_id' => $mantra->id,
            'name' => 'Bản dịch tham khảo',
            'language' => 'vi',
        ]);

        $response = $this->getJson('/api/v1/mantras/chu-dai-bi/versions');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * 4. Default version hoạt động.
     */
    public function test_04_default_version_works(): void
    {
        $mantra = Mantra::factory()->published()->create(['slug' => 'chu-duoc-su']);

        MantraVersion::factory()->published()->create([
            'mantra_id' => $mantra->id,
            'name' => 'Bản thường',
            'is_default' => false,
        ]);

        $defaultVer = MantraVersion::factory()->published()->default()->create([
            'mantra_id' => $mantra->id,
            'name' => 'Bản mặc định Phạn văn',
            'language' => 'sa',
        ]);

        $response = $this->getJson('/api/v1/mantras/chu-duoc-su');

        $response->assertStatus(200);
        $this->assertSame($defaultVer->id, $response->json('data.default_version.id'));
        $this->assertSame('Bản mặc định Phạn văn', $response->json('data.default_version.name'));
    }

    /**
     * 5. List API chỉ trả published.
     */
    public function test_05_list_api_only_returns_published_mantras(): void
    {
        Mantra::factory()->published()->create(['title' => 'Chú Đại Bi', 'slug' => 'chu-dai-bi']);
        Mantra::factory()->published()->create(['title' => 'Chú Dược Sư', 'slug' => 'chu-duoc-su']);

        $response = $this->getJson('/api/v1/mantras');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'original_title',
                        'short_description',
                    ],
                ],
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /**
     * 6. Draft mantra không xuất hiện.
     */
    public function test_06_draft_mantra_does_not_appear(): void
    {
        Mantra::factory()->draft()->create(['slug' => 'chu-nhap']);
        Mantra::factory()->futurePublished()->create(['slug' => 'chu-tuong-lai']);

        $listRes = $this->getJson('/api/v1/mantras');
        $listRes->assertStatus(200);
        $this->assertCount(0, $listRes->json('data'));

        $detailDraft = $this->getJson('/api/v1/mantras/chu-nhap');
        $detailDraft->assertStatus(404);

        $detailFuture = $this->getJson('/api/v1/mantras/chu-tuong-lai');
        $detailFuture->assertStatus(404);
    }

    /**
     * 7. Draft mantra version không xuất hiện.
     */
    public function test_07_draft_mantra_version_does_not_appear(): void
    {
        $mantra = Mantra::factory()->published()->create(['slug' => 'chu-dai-bi']);

        $publishedVer = MantraVersion::factory()->published()->create([
            'mantra_id' => $mantra->id,
            'name' => 'Bản xuất bản',
        ]);

        MantraVersion::factory()->draft()->create([
            'mantra_id' => $mantra->id,
            'name' => 'Bản thử nghiệm nháp',
        ]);

        $response = $this->getJson('/api/v1/mantras/chu-dai-bi/versions');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($publishedVer->id, $data[0]['id']);
    }

    /**
     * 8. Detail mantra hoạt động.
     */
    public function test_08_detail_mantra_works(): void
    {
        $figure = BuddhistFigure::factory()->published()->create(['name' => 'Bồ Tát Quán Âm']);
        $tradition = Tradition::factory()->create(['name' => 'Bắc Truyền', 'slug' => 'bac-truyen']);

        $mantra = Mantra::factory()->published()->create([
            'title' => 'Chú Đại Bi',
            'slug' => 'chu-dai-bi',
            'original_title' => 'Mahā Karuṇā Dhāraṇī',
            'buddhist_figure_id' => $figure->id,
            'tradition_id' => $tradition->id,
            'short_description' => 'Thần chú cứu khổ cứu nạn.',
            'practice_guidance' => 'Mỗi ngày trì tụng 21 hoặc 108 biến.',
        ]);

        $response = $this->getJson('/api/v1/mantras/chu-dai-bi');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $mantra->id)
            ->assertJsonPath('data.title', 'Chú Đại Bi')
            ->assertJsonPath('data.original_title', 'Mahā Karuṇā Dhāraṇī')
            ->assertJsonPath('data.figure.name', 'Bồ Tát Quán Âm')
            ->assertJsonPath('data.tradition.slug', 'bac-truyen');
    }

    /**
     * 9. Versions endpoint hoạt động.
     */
    public function test_09_versions_endpoint_works(): void
    {
        $mantra = Mantra::factory()->published()->create(['slug' => 'chu-chuan-de']);

        $ver = MantraVersion::factory()->published()->create([
            'mantra_id' => $mantra->id,
            'name' => 'Phạn văn Chuẩn Đề',
            'content' => 'Namaḥ saptānāṃ samyaksambuddha koṭīnāṃ. Tadyathā: Oṃ cale cule cunde svāhā.',
            'transliteration' => 'Nam mo tat da nam tam mieu tam bo da cu chi nam...',
        ]);

        $response = $this->getJson('/api/v1/mantras/chu-chuan-de/versions');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $ver->id)
            ->assertJsonPath('data.0.name', 'Phạn văn Chuẩn Đề')
            ->assertJsonPath('data.0.content', 'Namaḥ saptānāṃ samyaksambuddha koṭīnāṃ. Tadyathā: Oṃ cale cule cunde svāhā.');
    }

    /**
     * 10. Direct version endpoint không expose draft.
     */
    public function test_10_direct_version_endpoint_does_not_expose_draft(): void
    {
        $mantra = Mantra::factory()->published()->create();
        $publishedVer = MantraVersion::factory()->published()->create(['mantra_id' => $mantra->id]);
        $draftVer = MantraVersion::factory()->draft()->create(['mantra_id' => $mantra->id]);

        $draftMantra = Mantra::factory()->draft()->create();
        $verInDraftMantra = MantraVersion::factory()->published()->create(['mantra_id' => $draftMantra->id]);

        // 1. Published version in published mantra -> 200
        $resPub = $this->getJson("/api/v1/mantra-versions/{$publishedVer->id}");
        $resPub->assertStatus(200);

        // 2. Draft version -> 404
        $resDraft = $this->getJson("/api/v1/mantra-versions/{$draftVer->id}");
        $resDraft->assertStatus(404);

        // 3. Published version in draft mantra -> 404
        $resVerDraftMantra = $this->getJson("/api/v1/mantra-versions/{$verInDraftMantra->id}");
        $resVerDraftMantra->assertStatus(404);
    }

    /**
     * 11. Search title hoạt động.
     */
    public function test_11_search_title_works(): void
    {
        Mantra::factory()->published()->create([
            'title' => 'Chú Đại Bi',
            'slug' => 'chu-dai-bi',
        ]);

        $response = $this->getJson('/api/v1/search?q=Chu Dai Bi&type=mantra');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
        $item = collect($response->json('data'))->firstWhere('slug', 'chu-dai-bi');
        $this->assertNotNull($item);
        $this->assertSame('mantra', $item['type']);
        $this->assertSame('Chú Đại Bi', $item['title']);
    }

    /**
     * 12. Search không dấu hoạt động.
     */
    public function test_12_search_unaccented_works(): void
    {
        Mantra::factory()->published()->create([
            'title' => 'Chú Dược Sư',
            'slug' => 'chu-duoc-su',
        ]);

        $response = $this->getJson('/api/v1/search?q=chu duoc su');

        $response->assertStatus(200);
        $item = collect($response->json('data'))->firstWhere('slug', 'chu-duoc-su');
        $this->assertNotNull($item);
        $this->assertSame('mantra', $item['type']);
    }

    /**
     * 13. Search transliteration hoạt động.
     */
    public function test_13_search_transliteration_works(): void
    {
        $mantra = Mantra::factory()->published()->create([
            'title' => 'Chú Lăng Nghiêm',
            'slug' => 'chu-lang-nghiem',
        ]);

        MantraVersion::factory()->published()->create([
            'mantra_id' => $mantra->id,
            'transliteration' => 'Sitātapatra Aparājita Dhāraṇī',
            'content' => 'Tadyathā Oṃ Anale Anale...',
        ]);

        $response = $this->getJson('/api/v1/search?q=Aparajita');

        $response->assertStatus(200);
        $item = collect($response->json('data'))->firstWhere('slug', 'chu-lang-nghiem');
        $this->assertNotNull($item);
        $this->assertSame('mantra', $item['type']);
    }

    /**
     * 14. Alias hoạt động.
     */
    public function test_14_alias_works(): void
    {
        $mantra = Mantra::factory()->published()->create([
            'title' => 'Chú Đại Bi',
            'slug' => 'chu-dai-bi',
        ]);

        $mantra->searchAliases()->create([
            'alias' => 'Nilakantha Dharani',
            'normalized_alias' => 'nilakantha dharani',
        ]);

        $response = $this->getJson('/api/v1/search?q=Nilakantha');

        $response->assertStatus(200);
        $item = collect($response->json('data'))->firstWhere('slug', 'chu-dai-bi');
        $this->assertNotNull($item);
        $this->assertSame('mantra', $item['type']);
    }

    /**
     * 15. Suggestions hoạt động.
     */
    public function test_15_suggestions_work(): void
    {
        Mantra::factory()->published()->create([
            'title' => 'Chú Vãng Sanh',
            'slug' => 'chu-vang-sanh',
        ]);

        $response = $this->getJson('/api/v1/search/suggestions?q=vang sanh');

        $response->assertStatus(200);
        $suggestions = $response->json('data');
        $match = collect($suggestions)->firstWhere('slug', 'chu-vang-sanh');
        $this->assertNotNull($match);
        $this->assertSame('mantra', $match['type']);
        $this->assertSame('Chú Vãng Sanh', $match['title']);
    }

    /**
     * 16. Unicode Sanskrit/Pali không bị hỏng.
     */
    public function test_16_unicode_sanskrit_pali_not_corrupted(): void
    {
        $originalContent = 'Oṃ Maṇi Padme Hūṃ. Tāre Tuttāre Ture Svāhā. Nīlakaṇṭha Dhāraṇī.';
        $originalTranslit = 'Oṃ Āḥ Hūṃ Vajra Guru Padma Siddhi Hūṃ';

        $mantra = Mantra::factory()->published()->create([
            'title' => 'Lục Tự Đại Minh Chú',
            'slug' => 'luc-tu-dai-minh-chu',
            'original_title' => 'Ṣaḍakṣarī Mahāvidyā',
        ]);

        $version = MantraVersion::factory()->published()->create([
            'mantra_id' => $mantra->id,
            'content' => $originalContent,
            'transliteration' => $originalTranslit,
        ]);

        // 1. Verify in database directly
        $fresh = MantraVersion::find($version->id);
        $this->assertSame($originalContent, $fresh->content);
        $this->assertSame($originalTranslit, $fresh->transliteration);

        // 2. Verify via API endpoint (must preserve diacritics without ASCII downgrade or question marks)
        $response = $this->getJson("/api/v1/mantra-versions/{$version->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.content', $originalContent)
            ->assertJsonPath('data.transliteration', $originalTranslit);

        $jsonRaw = $response->getContent();
        $this->assertStringContainsString('Oṃ', $jsonRaw);
        $this->assertStringContainsString('Hūṃ', $jsonRaw);
        $this->assertStringContainsString('Tāre', $jsonRaw);
        $this->assertStringContainsString('Svāhā', $jsonRaw);
        $this->assertStringContainsString('Ṣaḍakṣarī', $mantra->original_title);
    }

    /**
     * 17. Acceptance Criteria (Section 28 end-to-end flow).
     */
    public function test_17_end_to_end_acceptance_workflow(): void
    {
        // 1. Admin creates Quán Thế Âm Bồ Tát
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Quán Thế Âm Bồ Tát',
            'slug' => 'quan-the-am-bo-tat',
            'original_name' => 'Avalokiteśvara',
            'figure_type' => 'bodhisattva',
        ]);

        // 2. Admin creates Chú Đại Bi linked to Quán Thế Âm
        $mantra = Mantra::factory()->published()->create([
            'title' => 'Chú Đại Bi',
            'slug' => 'chu-dai-bi',
            'original_title' => 'Nīlakaṇṭha Dhāraṇī',
            'buddhist_figure_id' => $figure->id,
        ]);

        // 3. Admin creates MantraVersion
        $version = MantraVersion::factory()->published()->default()->create([
            'mantra_id' => $mantra->id,
            'name' => 'Phạn văn Chú Đại Bi',
            'content' => 'Namo ratnatrayāya, nama āryāvalokiteśvarāya bodhisattvāya...',
            'transliteration' => 'Namo ratna trayaya...',
        ]);

        // 4. Endpoints verification
        // - /api/v1/buddhist-figures
        $resFigures = $this->getJson('/api/v1/buddhist-figures');
        $resFigures->assertStatus(200);
        $this->assertNotNull(collect($resFigures->json('data'))->firstWhere('slug', 'quan-the-am-bo-tat'));

        // - /api/v1/buddhist-figures/{slug}
        $resFigureDetail = $this->getJson('/api/v1/buddhist-figures/quan-the-am-bo-tat');
        $resFigureDetail->assertStatus(200)
            ->assertJsonPath('data.slug', 'quan-the-am-bo-tat')
            ->assertJsonPath('data.mantras.0.slug', 'chu-dai-bi');

        // - /api/v1/mantras
        $resMantras = $this->getJson('/api/v1/mantras');
        $resMantras->assertStatus(200);
        $this->assertNotNull(collect($resMantras->json('data'))->firstWhere('slug', 'chu-dai-bi'));

        // - /api/v1/mantras/{slug}
        $resMantraDetail = $this->getJson('/api/v1/mantras/chu-dai-bi');
        $resMantraDetail->assertStatus(200)
            ->assertJsonPath('data.slug', 'chu-dai-bi')
            ->assertJsonPath('data.figure.slug', 'quan-the-am-bo-tat');

        // - /api/v1/mantras/{slug}/versions
        $resVersions = $this->getJson('/api/v1/mantras/chu-dai-bi/versions');
        $resVersions->assertStatus(200)
            ->assertJsonPath('data.0.id', $version->id);

        // - /api/v1/mantra-versions/{id}
        $resVerDetail = $this->getJson("/api/v1/mantra-versions/{$version->id}");
        $resVerDetail->assertStatus(200)
            ->assertJsonPath('data.id', $version->id)
            ->assertJsonPath('data.mantra.slug', 'chu-dai-bi');

        // - /api/v1/search
        $resSearchFig = $this->getJson('/api/v1/search?q=quan the am');
        $resSearchFig->assertStatus(200);
        $this->assertNotNull(collect($resSearchFig->json('data'))->firstWhere('slug', 'quan-the-am-bo-tat'));

        $resSearchMantra = $this->getJson('/api/v1/search?q=chu dai bi');
        $resSearchMantra->assertStatus(200);
        $this->assertNotNull(collect($resSearchMantra->json('data'))->firstWhere('slug', 'chu-dai-bi'));

        // - /api/v1/search/suggestions
        $resSugFig = $this->getJson('/api/v1/search/suggestions?q=quan the am');
        $resSugFig->assertStatus(200);
        $this->assertNotNull(collect($resSugFig->json('data'))->firstWhere('slug', 'quan-the-am-bo-tat'));

        $resSugMantra = $this->getJson('/api/v1/search/suggestions?q=dai bi');
        $resSugMantra->assertStatus(200);
        $this->assertNotNull(collect($resSugMantra->json('data'))->firstWhere('slug', 'chu-dai-bi'));
    }
}
