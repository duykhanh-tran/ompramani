<?php

namespace Tests\Feature;

use App\Filament\Resources\BuddhistFigures\BuddhistFigureResource;
use App\Models\BuddhistFigure;
use App\Models\Mantra;
use App\Models\Tradition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuddhistFigureApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Admin tạo / quản lý Buddhist Figure qua Filament.
     */
    public function test_01_admin_can_crud_buddhist_figure(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);
        $this->assertTrue(BuddhistFigureResource::canViewAny());
        $this->assertTrue(BuddhistFigureResource::canCreate());

        $figure = BuddhistFigure::factory()->create([
            'name' => 'Quán Thế Âm Bồ Tát',
            'slug' => 'quan-the-am-bo-tat',
            'figure_type' => 'bodhisattva',
            'status' => 'published',
        ]);

        $this->assertTrue(BuddhistFigureResource::canEdit($figure));
        $this->assertTrue(BuddhistFigureResource::canDelete($figure));
        $this->assertDatabaseHas('buddhist_figures', ['slug' => 'quan-the-am-bo-tat']);
    }

    /**
     * 2. User thường không CRUD được.
     */
    public function test_02_regular_user_cannot_crud_buddhist_figure(): void
    {
        $regularUser = User::factory()->create(['role' => 'user']);
        $figure = BuddhistFigure::factory()->create();

        $this->actingAs($regularUser);
        $this->assertFalse(BuddhistFigureResource::canViewAny());
        $this->assertFalse(BuddhistFigureResource::canCreate());
        $this->assertFalse(BuddhistFigureResource::canEdit($figure));
        $this->assertFalse(BuddhistFigureResource::canDelete($figure));

        $response = $this->get('/admin/buddhist-figures');
        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    /**
     * 3. List API chỉ trả published.
     */
    public function test_03_list_api_only_returns_published_figures(): void
    {
        BuddhistFigure::factory()->published()->create([
            'name' => 'Thích Ca Mâu Ni Phật',
            'slug' => 'thich-ca-mau-ni-phat',
        ]);

        BuddhistFigure::factory()->published()->create([
            'name' => 'A Di Đà Phật',
            'slug' => 'a-di-da-phat',
        ]);

        $response = $this->getJson('/api/v1/buddhist-figures');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'original_name',
                        'figure_type',
                        'short_description',
                        'thumbnail',
                    ],
                ],
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /**
     * 4. Draft figure không xuất hiện.
     */
    public function test_04_draft_and_future_figures_do_not_appear(): void
    {
        BuddhistFigure::factory()->draft()->create([
            'name' => 'Bản Nháp Bồ Tát',
            'slug' => 'ban-nhap-bo-tat',
        ]);

        BuddhistFigure::factory()->futurePublished()->create([
            'name' => 'Tương Lai Phật',
            'slug' => 'tuong-lai-phat',
        ]);

        $listResponse = $this->getJson('/api/v1/buddhist-figures');
        $listResponse->assertStatus(200);
        $this->assertCount(0, $listResponse->json('data'));

        $detailDraft = $this->getJson('/api/v1/buddhist-figures/ban-nhap-bo-tat');
        $detailDraft->assertStatus(404);

        $detailFuture = $this->getJson('/api/v1/buddhist-figures/tuong-lai-phat');
        $detailFuture->assertStatus(404);
    }

    /**
     * 5. Detail bằng slug.
     */
    public function test_05_detail_by_slug(): void
    {
        $tradition = Tradition::factory()->create(['name' => 'Bắc Truyền', 'slug' => 'bac-truyen']);
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Địa Tạng Bồ Tát',
            'slug' => 'dia-tang-bo-tat',
            'original_name' => 'Kṣitigarbha',
            'figure_type' => 'bodhisattva',
            'short_description' => 'Đại nguyện cứu độ chúng sinh nơi địa ngục.',
            'biography' => 'Hành trạng cứu mẹ nhiều kiếp.',
            'great_vows' => 'Địa ngục vị không, thệ bất thành Phật.',
            'iconography' => 'Tay cầm tích trượng, tay cầm ngọc như ý.',
            'practice_guidance' => 'Trì niệm danh hiệu Nam Mô Địa Tạng Vương Bồ Tát.',
            'tradition_id' => $tradition->id,
        ]);

        $response = $this->getJson('/api/v1/buddhist-figures/dia-tang-bo-tat');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $figure->id)
            ->assertJsonPath('data.name', 'Địa Tạng Bồ Tát')
            ->assertJsonPath('data.slug', 'dia-tang-bo-tat')
            ->assertJsonPath('data.original_name', 'Kṣitigarbha')
            ->assertJsonPath('data.figure_type', 'bodhisattva')
            ->assertJsonPath('data.great_vows', 'Địa ngục vị không, thệ bất thành Phật.')
            ->assertJsonPath('data.tradition.slug', 'bac-truyen');
    }

    /**
     * 6. Slug không tồn tại → 404.
     */
    public function test_06_non_existent_slug_returns_404(): void
    {
        $response = $this->getJson('/api/v1/buddhist-figures/khong-ton-tai');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Không tìm thấy nội dung.',
            ]);
    }

    /**
     * 7. Filter figure_type.
     */
    public function test_07_filter_figure_type(): void
    {
        BuddhistFigure::factory()->buddha()->published()->create(['name' => 'Phật Thích Ca', 'slug' => 'phat-thich-ca']);
        BuddhistFigure::factory()->bodhisattva()->published()->create(['name' => 'Bồ Tát Quan Âm', 'slug' => 'bo-tat-quan-am']);

        $resBuddha = $this->getJson('/api/v1/buddhist-figures?type=buddha');
        $resBuddha->assertStatus(200);
        $this->assertCount(1, $resBuddha->json('data'));
        $this->assertSame('buddha', $resBuddha->json('data.0.figure_type'));

        $resBodhisattva = $this->getJson('/api/v1/buddhist-figures?type=bodhisattva');
        $resBodhisattva->assertStatus(200);
        $this->assertCount(1, $resBodhisattva->json('data'));
        $this->assertSame('bodhisattva', $resBodhisattva->json('data.0.figure_type'));
    }

    /**
     * 8. Filter tradition.
     */
    public function test_08_filter_tradition(): void
    {
        $bacTruyen = Tradition::factory()->create(['name' => 'Bắc Truyền', 'slug' => 'bac-truyen']);
        $namTruyen = Tradition::factory()->create(['name' => 'Nam Truyền', 'slug' => 'nam-truyen']);

        $fig1 = BuddhistFigure::factory()->published()->create([
            'name' => 'Phật Bắc Truyền',
            'tradition_id' => $bacTruyen->id,
        ]);
        $fig2 = BuddhistFigure::factory()->published()->create([
            'name' => 'Phật Nam Truyền',
            'tradition_id' => $namTruyen->id,
        ]);

        $resBac = $this->getJson('/api/v1/buddhist-figures?tradition=bac-truyen');
        $resBac->assertStatus(200);
        $this->assertCount(1, $resBac->json('data'));
        $this->assertSame($fig1->id, $resBac->json('data.0.id'));

        $resNam = $this->getJson('/api/v1/buddhist-figures?tradition=nam-truyen');
        $resNam->assertStatus(200);
        $this->assertCount(1, $resNam->json('data'));
        $this->assertSame($fig2->id, $resNam->json('data.0.id'));
    }

    /**
     * 9. Related mantras chỉ trả published.
     */
    public function test_09_related_mantras_only_return_published(): void
    {
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Quán Thế Âm Bồ Tát',
            'slug' => 'quan-the-am-bo-tat',
        ]);

        // Published mantra
        $publishedMantra = Mantra::factory()->published()->create([
            'buddhist_figure_id' => $figure->id,
            'title' => 'Chú Đại Bi',
            'slug' => 'chu-dai-bi',
        ]);

        // Draft mantra
        $draftMantra = Mantra::factory()->draft()->create([
            'buddhist_figure_id' => $figure->id,
            'title' => 'Chú Lăng Nghiêm Nháp',
            'slug' => 'chu-lang-nghiem-nhap',
        ]);

        $response = $this->getJson('/api/v1/buddhist-figures/quan-the-am-bo-tat');

        $response->assertStatus(200);
        $mantras = $response->json('data.mantras');
        $this->assertCount(1, $mantras);
        $this->assertSame($publishedMantra->id, $mantras[0]['id']);
        $this->assertSame('Chú Đại Bi', $mantras[0]['title']);
    }

    /**
     * 10. Search tìm được Figure.
     */
    public function test_10_search_finds_figure(): void
    {
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Dược Sư Lưu Ly Quang Vương Phật',
            'slug' => 'duoc-su-phat',
            'original_name' => 'Bhaiṣajyaguru',
            'short_description' => 'Đức Phật trị liệu thân tâm cứu độ khổ đau.',
        ]);

        $response = $this->getJson('/api/v1/search?q=Duoc Su&type=figure');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
        $item = collect($response->json('data'))->firstWhere('slug', 'duoc-su-phat');
        $this->assertNotNull($item);
        $this->assertSame('figure', $item['type']);
        $this->assertSame('Dược Sư Lưu Ly Quang Vương Phật', $item['title']);
    }

    /**
     * 11. Alias tìm được Figure.
     */
    public function test_11_alias_finds_figure(): void
    {
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Quán Thế Âm Bồ Tát',
            'slug' => 'quan-the-am-bo-tat',
            'original_name' => 'Avalokiteśvara',
        ]);

        $figure->searchAliases()->create([
            'alias' => 'Quan Âm',
            'normalized_alias' => 'quan am',
        ]);

        $response = $this->getJson('/api/v1/search?q=quan am');

        $response->assertStatus(200);
        $item = collect($response->json('data'))->firstWhere('slug', 'quan-the-am-bo-tat');
        $this->assertNotNull($item);
        $this->assertSame('figure', $item['type']);
    }

    /**
     * 12. Suggestion tìm được Figure.
     */
    public function test_12_suggestion_finds_figure(): void
    {
        BuddhistFigure::factory()->published()->create([
            'name' => 'Văn Thù Sư Lợi Bồ Tát',
            'slug' => 'van-thu-su-loi',
        ]);

        $response = $this->getJson('/api/v1/search/suggestions?q=van thu');

        $response->assertStatus(200);
        $suggestions = $response->json('data');
        $match = collect($suggestions)->firstWhere('slug', 'van-thu-su-loi');
        $this->assertNotNull($match);
        $this->assertSame('figure', $match['type']);
        $this->assertSame('Văn Thù Sư Lợi Bồ Tát', $match['title']);
    }
}
