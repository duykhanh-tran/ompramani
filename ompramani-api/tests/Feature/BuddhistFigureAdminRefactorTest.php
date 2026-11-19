<?php

namespace Tests\Feature;

use App\Filament\Resources\BuddhistFigures\BuddhistFigureResource;
use App\Filament\Resources\BuddhistFigures\Pages\CreateBuddhistFigure;
use App\Filament\Resources\BuddhistFigures\Pages\EditBuddhistFigure;
use App\Filament\Resources\BuddhistFigures\Schemas\BuddhistFigureForm;
use App\Models\BuddhistFigure;
use App\Models\BuddhistFigureVow;
use App\Models\Mantra;
use App\Models\Sutra;
use App\Models\Tradition;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BuddhistFigureAdminRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * 1. Form có đúng 3 Sections trực quan:
     * - Section 1: Thông tin cơ bản (name, slug, invocation_text, figure_type, tradition_id, status, thumbnail, sutras, mantras)
     * - Section 2: Thông tin giới thiệu chi tiết (biography RichEditor)
     * - Section 3: Đại nguyện (vows_introduction RichEditor, vows Repeater)
     */
    public function test_01_form_has_three_sections_and_exact_specified_fields(): void
    {
        $schema = new Schema;
        $configured = BuddhistFigureForm::configure($schema);
        $components = $configured->getComponents();

        $this->assertCount(3, $components, 'BuddhistFigureForm must have exactly 3 Sections.');

        // Section 1: Thông tin cơ bản
        $this->assertInstanceOf(Section::class, $components[0]);
        $this->assertSame('Thông tin cơ bản', $components[0]->getHeading());
        $sec1Children = $components[0]->getDefaultChildComponents();
        $sec1Names = array_map(fn ($c) => $c->getName(), $sec1Children);
        $expectedSec1 = [
            'name',
            'slug',
            'invocation_text',
            'figure_type',
            'tradition_id',
            'status',
            'thumbnail',
            'sutras',
            'mantras',
        ];
        $this->assertEquals($expectedSec1, $sec1Names);

        // Thumbnail is FileUpload
        $thumbnailField = collect($sec1Children)->firstWhere(fn ($c) => $c->getName() === 'thumbnail');
        $this->assertInstanceOf(FileUpload::class, $thumbnailField);

        // Section 2: Thông tin giới thiệu chi tiết
        $this->assertInstanceOf(Section::class, $components[1]);
        $this->assertSame('Thông tin giới thiệu chi tiết', $components[1]->getHeading());
        $sec2Children = $components[1]->getDefaultChildComponents();
        $this->assertCount(1, $sec2Children);
        $this->assertSame('biography', $sec2Children[0]->getName());
        $this->assertInstanceOf(RichEditor::class, $sec2Children[0]);

        // Section 3: Đại nguyện
        $this->assertInstanceOf(Section::class, $components[2]);
        $this->assertSame('Đại nguyện', $components[2]->getHeading());
        $sec3Children = $components[2]->getDefaultChildComponents();
        $sec3Names = array_map(fn ($c) => $c->getName(), $sec3Children);
        $this->assertEquals(['vows_introduction', 'vows'], $sec3Names);
        $this->assertInstanceOf(RichEditor::class, $sec3Children[0]);
        $this->assertInstanceOf(Repeater::class, $sec3Children[1]);
    }

    /**
     * 2. Các trường thừa đã bị loại bỏ khỏi Form và không còn required.
     */
    public function test_02_removed_fields_are_not_present_in_form(): void
    {
        $schema = new Schema;
        $configured = BuddhistFigureForm::configure($schema);
        $components = $configured->getComponents();

        $allFieldNames = [];
        foreach ($components as $section) {
            if ($section instanceof Section) {
                foreach ($section->getDefaultChildComponents() as $child) {
                    $allFieldNames[] = $child->getName();
                }
            }
        }

        $this->assertNotContains('original_name', $allFieldNames);
        $this->assertNotContains('traditions', $allFieldNames);
        $this->assertNotContains('published_at', $allFieldNames);
        $this->assertNotContains('short_description', $allFieldNames);
        $this->assertNotContains('great_vows', $allFieldNames); // Replaced by vows_introduction & vows
        $this->assertNotContains('iconography', $allFieldNames);
        $this->assertNotContains('practice_guidance', $allFieldNames);
    }

    /**
     * 3. Có thể tạo mới BuddhistFigure qua Filament, mặc định status là draft.
     */
    public function test_03_can_create_buddhist_figure_with_draft_status_default(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $tradition = Tradition::factory()->create(['name' => 'Bắc Truyền']);

        Livewire::test(CreateBuddhistFigure::class)
            ->fillForm([
                'name' => 'Bồ Tát Quán Thế Âm',
                'slug' => 'bo-tat-quan-the-am',
                'invocation_text' => 'Nam mô Đại Bi Quán Thế Âm Bồ Tát',
                'figure_type' => 'bodhisattva',
                'tradition_id' => $tradition->id,
                'status' => 'draft',
                'biography' => '<h2>Tiểu sử</h2><p>Hiện thân lòng đại từ đại bi...</p>',
                'vows_introduction' => '<p>Mười hai đại nguyện của Đức Bồ Tát Quán Thế Âm...</p>',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('buddhist_figures', [
            'name' => 'Bồ Tát Quán Thế Âm',
            'slug' => 'bo-tat-quan-the-am',
            'invocation_text' => 'Nam mô Đại Bi Quán Thế Âm Bồ Tát',
            'figure_type' => 'bodhisattva',
            'status' => 'draft',
        ]);
    }

    /**
     * 4. Slug uniqueness is validated.
     */
    public function test_04_slug_uniqueness_is_validated_on_create(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        BuddhistFigure::factory()->create([
            'name' => 'Đức Phật A Di Đà',
            'slug' => 'duc-phat-a-di-da',
        ]);

        Livewire::test(CreateBuddhistFigure::class)
            ->fillForm([
                'name' => 'A Di Đà Phật Mới',
                'slug' => 'duc-phat-a-di-da',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    /**
     * 5. Upload ảnh từ máy tính lưu vào disk public.
     */
    public function test_05_can_upload_thumbnail_image_file(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $file = UploadedFile::fake()->image('quan-am.webp', 600, 800);

        Livewire::test(CreateBuddhistFigure::class)
            ->fillForm([
                'name' => 'Đức Bồ Tát Đại Thế Chí',
                'slug' => 'bo-tat-dai-the-chi',
                'figure_type' => 'bodhisattva',
                'thumbnail' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $figure = BuddhistFigure::where('slug', 'bo-tat-dai-the-chi')->firstOrFail();
        $this->assertNotNull($figure->thumbnail);
        Storage::disk('public')->assertExists($figure->thumbnail);
    }

    /**
     * 6. Liên kết Kinh và Thần Chú hoạt động đồng bộ.
     */
    public function test_06_can_link_sutras_and_mantras(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $sutra = Sutra::factory()->create(['title' => 'Kinh Phổ Môn', 'slug' => 'kinh-pho-mon']);
        $mantra = Mantra::factory()->create(['title' => 'Chú Đại Bi', 'slug' => 'chu-dai-bi']);

        $figure = BuddhistFigure::factory()->create([
            'name' => 'Bồ Tát Quán Âm',
            'slug' => 'bo-tat-quan-am',
        ]);

        Livewire::test(EditBuddhistFigure::class, ['record' => $figure->getRouteKey()])
            ->fillForm([
                'sutras' => [$sutra->id],
                'mantras' => [$mantra->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $figure->refresh();

        // Sutra pivot table
        $this->assertDatabaseHas('buddhist_figure_sutra', [
            'buddhist_figure_id' => $figure->id,
            'sutra_id' => $sutra->id,
        ]);
        $this->assertTrue($figure->sutras->contains($sutra));

        // Mantra inverse relation (buddhist_figure_id)
        $mantra->refresh();
        $this->assertEquals($figure->id, $mantra->buddhist_figure_id);
    }

    /**
     * 7. Biography RichEditor bảo toàn định dạng HTML và Unicode tiếng Việt.
     */
    public function test_07_rich_biography_preserves_formatting_headings_and_unicode(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $figure = BuddhistFigure::factory()->create();

        $richHtml = '<h2>Hạnh Nguyện Từ Bi</h2><p>Đức <strong>Quán Thế Âm</strong> tầm thinh cứu khổ cứu nạn chúng sinh khắp cõi Ta Bà.</p><ul><li><p>Độ sinh vô ngại</p></li><li><p>Thần lực vi diệu</p></li></ul>';

        Livewire::test(EditBuddhistFigure::class, ['record' => $figure->getRouteKey()])
            ->fillForm([
                'biography' => $richHtml,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $figure->refresh();
        $this->assertSame($richHtml, $figure->biography);
    }

    /**
     * 8. Vows introduction & từng mục Đại nguyện có thứ tự sort_order.
     */
    public function test_08_great_vows_introduction_and_ordered_vow_items(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $figure = BuddhistFigure::factory()->create([
            'name' => 'Đức Phật Dược Sư',
            'slug' => 'duc-phat-duoc-su',
        ]);

        $vowsIntro = '<p>Mười hai đại nguyện của Đức Phật Dược Sư Lưu Ly Quang Vương Như Lai khi còn hành đạo Bồ Tát.</p>';

        Livewire::test(EditBuddhistFigure::class, ['record' => $figure->getRouteKey()])
            ->fillForm([
                'vows_introduction' => $vowsIntro,
                'vows' => [
                    [
                        'title' => 'Nguyện thứ nhất',
                        'content' => 'Phát hào quang sáng suốt rực rỡ chiếu khắp vô lượng vô số thế giới.',
                    ],
                    [
                        'title' => 'Nguyện thứ hai',
                        'content' => 'Thân như ngọc lưu ly, trong ngoài sáng suốt, tinh sạch hoàn toàn.',
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $figure->refresh();
        $this->assertSame($vowsIntro, $figure->vows_introduction);
        $this->assertCount(2, $figure->vows);

        $firstVow = $figure->vows->first();
        $this->assertSame('Nguyện thứ nhất', $firstVow->title);
        $this->assertStringContainsString('Phát hào quang', $firstVow->content);
    }

    /**
     * 9. Xóa BuddhistFigure thì các Đại nguyện con tự động xóa cascade, không để lại orphan records.
     */
    public function test_09_vows_cascade_delete_with_buddhist_figure(): void
    {
        $figure = BuddhistFigure::factory()->create();

        $vow1 = BuddhistFigureVow::create([
            'buddhist_figure_id' => $figure->id,
            'title' => 'Nguyện 1',
            'content' => 'Nội dung 1',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('buddhist_figure_vows', ['id' => $vow1->id]);

        $figure->delete();

        $this->assertDatabaseMissing('buddhist_figure_vows', ['id' => $vow1->id]);
    }

    /**
     * 10. API GET /api/v1/buddhist-figures/{slug} trả về rich biography, vows_introduction,
     * danh sách vows có thứ tự, sutras, mantras và ảnh đại diện.
     */
    public function test_10_api_returns_rich_content_vows_sutras_and_mantras(): void
    {
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Bồ Tát Địa Tạng',
            'slug' => 'dia-tang-bo-tat',
            'biography' => '<p>Địa Ngục Vị Không Thệ Bất Thành Phật</p>',
            'vows_introduction' => '<p>Đại nguyện cứu vớt chúng sinh cõi u minh</p>',
            'thumbnail' => 'buddhist-figures/dia-tang.webp',
        ]);

        BuddhistFigureVow::create([
            'buddhist_figure_id' => $figure->id,
            'title' => 'Nguyện độ u đồ',
            'content' => 'Độ tận chúng sinh phương chứng Bồ Đề',
            'sort_order' => 1,
        ]);

        $sutra = Sutra::factory()->create(['title' => 'Kinh Địa Tạng Bổn Nguyện', 'status' => 'published']);
        $figure->sutras()->attach($sutra->id);

        $mantra = Mantra::factory()->published()->create([
            'title' => 'Địa Tạng Bồ Tát Tâm Chú',
            'buddhist_figure_id' => $figure->id,
        ]);

        $response = $this->getJson("/api/v1/buddhist-figures/{$figure->slug}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Bồ Tát Địa Tạng')
            ->assertJsonPath('data.biography', '<p>Địa Ngục Vị Không Thệ Bất Thành Phật</p>')
            ->assertJsonPath('data.vows_introduction', '<p>Đại nguyện cứu vớt chúng sinh cõi u minh</p>')
            ->assertJsonPath('data.thumbnail', 'buddhist-figures/dia-tang.webp')
            ->assertJsonPath('data.vows.0.title', 'Nguyện độ u đồ')
            ->assertJsonPath('data.sutras.0.title', 'Kinh Địa Tạng Bổn Nguyện')
            ->assertJsonPath('data.mantras.0.title', 'Địa Tạng Bồ Tát Tâm Chú');
    }

    /**
     * 11. Mantra kế thừa ảnh đại diện từ BuddhistFigure thumbnail mà không duplicate.
     */
    public function test_11_mantra_inherits_image_automatically_from_figure_thumbnail(): void
    {
        $figure = BuddhistFigure::factory()->published()->create([
            'thumbnail' => 'buddhist-figures/chuan-de.webp',
        ]);

        $mantra = Mantra::factory()->create([
            'buddhist_figure_id' => $figure->id,
            'thumbnail' => null,
        ]);

        // Accessor inherits from figure thumbnail
        $this->assertSame('buddhist-figures/chuan-de.webp', $mantra->thumbnail_url);

        // Update figure thumbnail
        $figure->update(['thumbnail' => 'buddhist-figures/chuan-de-moi.webp']);

        // Mantra instance automatically reflects updated thumbnail without re-saving
        $this->assertSame('buddhist-figures/chuan-de-moi.webp', $mantra->fresh()->thumbnail_url);
    }

    /**
     * 12. Non-admin user cannot access BuddhistFigure resource.
     */
    public function test_12_non_admin_cannot_access_buddhist_figure_filament_resource(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->assertFalse(BuddhistFigureResource::canViewAny());
        $this->assertFalse(BuddhistFigureResource::canCreate());
    }
}
