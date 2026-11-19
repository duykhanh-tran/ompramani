<?php

namespace Tests\Feature;

use App\Filament\Resources\Mantras\MantraResource;
use App\Filament\Resources\Mantras\Pages\CreateMantra;
use App\Filament\Resources\Mantras\Pages\EditMantra;
use App\Filament\Resources\Mantras\Schemas\MantraForm;
use App\Models\BuddhistFigure;
use App\Models\Mantra;
use App\Models\User;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MantraAdminSimplificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. MantraForm có đúng 7 fields theo đặc tả hợp nhất Trì Chú:
     * - title
     * - slug
     * - buddhist_figure_id
     * - short_description
     * - original_text
     * - vietnamese_transcription
     * - audio_path (Audio trì niệm)
     */
    public function test_mantra_form_has_exactly_7_fields(): void
    {
        $schema = new Schema;
        $configured = MantraForm::configure($schema);
        $components = $configured->getComponents();

        $this->assertCount(7, $components, 'MantraForm must have exactly 7 components.');

        $names = array_map(fn ($c) => $c->getName(), $components);

        $expectedNames = [
            'title',
            'slug',
            'buddhist_figure_id',
            'short_description',
            'original_text',
            'vietnamese_transcription',
            'audio_path',
        ];

        $this->assertEquals($expectedNames, $names);

        // Ensure removed fields are NOT present
        $this->assertNotContains('thumbnail', $names);
        $this->assertNotContains('status', $names);
        $this->assertNotContains('original_title', $names);
        $this->assertNotContains('tradition_id', $names);
        $this->assertNotContains('practice_guidance', $names);
        $this->assertNotContains('published_at', $names);
    }

    /**
     * 2. Mantra edit form relations is empty (no sub-relation manager clutter).
     */
    public function test_mantra_resource_has_no_sub_relation_managers(): void
    {
        $this->assertEmpty(MantraResource::getRelations(), 'MantraResource must not contain sub-relation managers.');
    }

    /**
     * 3. Can create a Mantra via Filament form with status defaulting to 'draft'.
     */
    public function test_can_create_mantra_via_filament_create_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Bồ Tát Quán Thế Âm',
            'thumbnail' => 'buddhist-figures/quan-am.webp',
        ]);

        $originalText = "Oṃ Maṇi Padme Hūṃ\nnamo ratnatrayāya namaḥ āryāvalokiteśvarāya";
        $transcription = "Úm ma ni bát mê hồng\nNam mô hắc ra đát na đa ra dạ da";

        Livewire::test(CreateMantra::class)
            ->fillForm([
                'title' => 'Lục Tự Đại Minh Chú',
                'slug' => 'luc-tu-dai-minh-chu',
                'buddhist_figure_id' => $figure->id,
                'short_description' => 'Thần chú sáu chữ của Bồ Tát Quán Thế Âm',
                'original_text' => $originalText,
                'vietnamese_transcription' => $transcription,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('mantras', [
            'title' => 'Lục Tự Đại Minh Chú',
            'slug' => 'luc-tu-dai-minh-chu',
            'buddhist_figure_id' => $figure->id,
            'status' => 'draft',
        ]);

        $mantra = Mantra::where('slug', 'luc-tu-dai-minh-chu')->firstOrFail();
        $this->assertSame($originalText, $mantra->original_text);
        $this->assertSame($transcription, $mantra->vietnamese_transcription);
    }

    /**
     * 4. Slug uniqueness is validated.
     */
    public function test_slug_uniqueness_is_validated_on_create(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Mantra::factory()->create([
            'title' => 'Chú Đại Bi',
            'slug' => 'chu-dai-bi',
        ]);

        Livewire::test(CreateMantra::class)
            ->fillForm([
                'title' => 'Chú Đại Bi 2',
                'slug' => 'chu-dai-bi',
                'short_description' => 'Trùng slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    /**
     * 5. Can update Mantra via Filament edit page.
     */
    public function test_can_update_mantra_via_filament_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $mantra = Mantra::factory()->create([
            'title' => 'Chú Dược Sư',
            'slug' => 'chu-duoc-su',
            'original_text' => 'namo bhagavate bhaiṣajyaguru',
            'vietnamese_transcription' => 'Nam mô bạt già phạt đế bệ sát xã',
        ]);

        $newFigure = BuddhistFigure::factory()->published()->create([
            'name' => 'Đức Phật Dược Sư',
            'thumbnail' => 'buddhist-figures/duoc-su.webp',
        ]);

        Livewire::test(EditMantra::class, ['record' => $mantra->getRouteKey()])
            ->fillForm([
                'title' => 'Chú Dược Sư Quán Đảnh',
                'slug' => 'chu-duoc-su',
                'buddhist_figure_id' => $newFigure->id,
                'short_description' => 'Mô tả cập nhật',
                'original_text' => 'namo bhagavate bhaiṣajyaguru vaiḍūryaprabharājāya',
                'vietnamese_transcription' => 'Nam mô bạt già phạt đế bệ sát xã lũ lô thích lưu ly',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $mantra->refresh();
        $this->assertSame('Chú Dược Sư Quán Đảnh', $mantra->title);
        $this->assertEquals($newFigure->id, $mantra->buddhist_figure_id);
        $this->assertSame('namo bhagavate bhaiṣajyaguru vaiḍūryaprabharājāya', $mantra->original_text);
        $this->assertSame('Nam mô bạt già phạt đế bệ sát xã lũ lô thích lưu ly', $mantra->vietnamese_transcription);
    }

    /**
     * 6. Image auto-inheritance from linked BuddhistFigure.
     */
    public function test_mantra_inherits_image_automatically_from_buddhist_figure(): void
    {
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Bồ Tát Chuẩn Đề',
            'thumbnail' => 'buddhist-figures/chuan-de.webp',
        ]);

        $mantra = Mantra::factory()->create([
            'title' => 'Chú Chuẩn Đề',
            'slug' => 'chu-chuan-de',
            'buddhist_figure_id' => $figure->id,
            'thumbnail' => null,
        ]);

        $this->assertSame('buddhist-figures/chuan-de.webp', $mantra->thumbnail_url);
    }

    /**
     * 7. When BuddhistFigure updates photo, Mantra immediately reflects it dynamically.
     */
    public function test_mantra_reflects_figure_photo_update_dynamically(): void
    {
        $figure = BuddhistFigure::factory()->published()->create([
            'thumbnail' => 'buddhist-figures/old-photo.webp',
        ]);

        $mantra = Mantra::factory()->create([
            'buddhist_figure_id' => $figure->id,
            'thumbnail' => null,
        ]);

        $this->assertSame('buddhist-figures/old-photo.webp', $mantra->fresh()->thumbnail_url);

        // Update figure thumbnail
        $figure->update(['thumbnail' => 'buddhist-figures/new-photo.webp']);

        // Mantra instance without re-saving reflects updated photo
        $this->assertSame('buddhist-figures/new-photo.webp', $mantra->fresh()->thumbnail_url);
    }

    /**
     * 8. When changing BuddhistFigure on Mantra, photo immediately switches to new figure.
     */
    public function test_mantra_switches_photo_when_figure_relationship_changes(): void
    {
        $figureA = BuddhistFigure::factory()->published()->create(['thumbnail' => 'fig-a.webp']);
        $figureB = BuddhistFigure::factory()->published()->create(['thumbnail' => 'fig-b.webp']);

        $mantra = Mantra::factory()->create([
            'buddhist_figure_id' => $figureA->id,
            'thumbnail' => null,
        ]);

        $this->assertSame('fig-a.webp', $mantra->thumbnail_url);

        $mantra->update(['buddhist_figure_id' => $figureB->id]);

        $this->assertSame('fig-b.webp', $mantra->fresh()->thumbnail_url);
    }

    /**
     * 9. Fallback behavior when figure has no photo or when figure is null.
     */
    public function test_mantra_image_fallback_behavior(): void
    {
        // 9a. Figure with null thumbnail
        $figureNoThumb = BuddhistFigure::factory()->published()->create(['thumbnail' => null]);
        $mantraWithEmptyFigure = Mantra::factory()->create([
            'buddhist_figure_id' => $figureNoThumb->id,
            'thumbnail' => null,
        ]);
        $this->assertNull($mantraWithEmptyFigure->thumbnail_url);

        // 9b. No figure, but legacy thumbnail exists
        $mantraLegacy = Mantra::factory()->create([
            'buddhist_figure_id' => null,
            'thumbnail' => 'legacy/mantra.webp',
        ]);
        $this->assertSame('legacy/mantra.webp', $mantraLegacy->thumbnail_url);

        // 9c. No figure and no legacy thumbnail
        $mantraNone = Mantra::factory()->create([
            'buddhist_figure_id' => null,
            'thumbnail' => null,
        ]);
        $this->assertNull($mantraNone->thumbnail_url);
    }

    /**
     * 10. API GET /api/v1/mantras/{slug} returns original_text, vietnamese_transcription,
     * buddhist_figure, and inherited thumbnail.
     */
    public function test_mantra_api_returns_core_fields_and_inherited_thumbnail(): void
    {
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Bồ Tát Địa Tạng',
            'slug' => 'dia-tang-vuong-bo-tat',
            'thumbnail' => 'buddhist-figures/dia-tang.webp',
        ]);

        $originalText = "oṃ ha ha ha vismaye svāhā";
        $transcription = "Úm ha ha ha vi tư ma dạ sa bà ha";

        $mantra = Mantra::factory()->published()->create([
            'title' => 'Địa Tạng Bồ Tát Tâm Chú',
            'slug' => 'dia-tang-bo-tat-tam-chu',
            'buddhist_figure_id' => $figure->id,
            'original_text' => $originalText,
            'vietnamese_transcription' => $transcription,
            'thumbnail' => null,
        ]);

        $response = $this->getJson("/api/v1/mantras/{$mantra->slug}");

        $response->assertOk()
            ->assertJsonPath('data.title', 'Địa Tạng Bồ Tát Tâm Chú')
            ->assertJsonPath('data.original_text', $originalText)
            ->assertJsonPath('data.vietnamese_transcription', $transcription)
            ->assertJsonPath('data.thumbnail', 'buddhist-figures/dia-tang.webp')
            ->assertJsonPath('data.image_url', 'buddhist-figures/dia-tang.webp')
            ->assertJsonPath('data.figure.name', 'Bồ Tát Địa Tạng')
            ->assertJsonPath('data.audio_url', null);
    }

    /**
     * 11. API GET /api/v1/mantras/{slug} returns valid audio_url when audio_path is set.
     */
    public function test_mantra_api_returns_audio_url_when_audio_path_is_present(): void
    {
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Đức Phật A Di Đà',
            'slug' => 'duc-phat-a-di-da',
            'thumbnail' => 'buddhist-figures/a-di-da.webp',
        ]);

        $mantra = Mantra::factory()->published()->create([
            'title' => 'Vãng Sanh Quyết Định Chân Ngôn',
            'slug' => 'vang-sanh-than-chu',
            'buddhist_figure_id' => $figure->id,
            'audio_path' => 'mantras/audio/vang-sanh.mp3',
        ]);

        $response = $this->getJson("/api/v1/mantras/{$mantra->slug}");

        $response->assertOk()
            ->assertJsonPath('data.slug', 'vang-sanh-than-chu')
            ->assertJsonPath('data.audio_path', 'mantras/audio/vang-sanh.mp3');

        $this->assertStringContainsString('vang-sanh.mp3', (string) $response->json('data.audio_url'));
    }

    /**
     * 12. Draft Mantra is not accessible publicly.
     */
    public function test_draft_mantra_is_not_publicly_accessible(): void
    {
        $mantra = Mantra::factory()->create([
            'title' => 'Bản Nháp Chưa Duyệt',
            'slug' => 'ban-nhap-chua-duyet',
            'status' => 'draft',
        ]);

        $response = $this->getJson("/api/v1/mantras/{$mantra->slug}");
        $response->assertNotFound();
    }
}
