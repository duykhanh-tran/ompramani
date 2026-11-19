<?php

namespace Tests\Feature;

use App\Filament\Resources\ArticleCategories\ArticleCategoryResource;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Schemas\ArticleForm;
use App\Filament\Resources\ArticleTags\ArticleTagResource;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use App\Models\UserFavorite;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleAdminSimplificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Admin menu chỉ có Danh Mục Bài Viết và Bài Viết (trong Explore articles).
     * 2. Không còn mục Thẻ Bài Viết trong admin navigation.
     */
    public function test_01_admin_menu_has_only_categories_and_articles_and_no_tags(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        // Danh mục bài viết is in Explore with sort 1
        $this->assertTrue(ArticleCategoryResource::canViewAny());
        $this->assertSame('Explore', ArticleCategoryResource::getNavigationGroup());

        // Bài viết is in Explore with sort 2
        $this->assertTrue(ArticleResource::canViewAny());
        $this->assertSame('Explore', ArticleResource::getNavigationGroup());

        // Thẻ bài viết is removed from navigation and cannot be viewed
        $this->assertFalse(ArticleTagResource::shouldRegisterNavigation());
        $this->assertFalse(ArticleTagResource::canViewAny());
        $this->assertFalse(ArticleTagResource::canCreate());
    }

    /**
     * 3. Create Article form có đúng 8 fields.
     * 4. Edit Article form có đúng 8 fields.
     * 5. Removed fields (tags, excerpt, meta_title, meta_description, articleRelations) không còn trong form.
     */
    public function test_02_article_form_has_exactly_8_fields(): void
    {
        $schema = new Schema;
        ArticleForm::configure($schema);

        $components = $schema->getComponents();

        // Must have exactly 8 components
        $this->assertCount(8, $components, 'ArticleForm must have exactly 8 fields.');

        $names = array_map(fn ($c) => $c->getName(), $components);

        $expectedNames = [
            'title',
            'slug',
            'article_category_id',
            'author_id',
            'cover_image',
            'status',
            'published_at',
            'content',
        ];

        $this->assertEquals($expectedNames, $names);

        // Assert removed fields are NOT present
        $this->assertNotContains('tags', $names);
        $this->assertNotContains('excerpt', $names);
        $this->assertNotContains('meta_title', $names);
        $this->assertNotContains('meta_description', $names);
        $this->assertNotContains('articleRelations', $names);

        // Assert cover_image is FileUpload
        $coverComponent = collect($components)->firstWhere(fn ($c) => $c->getName() === 'cover_image');
        $this->assertInstanceOf(FileUpload::class, $coverComponent);

        // Assert content is RichEditor
        $contentComponent = collect($components)->firstWhere(fn ($c) => $c->getName() === 'content');
        $this->assertInstanceOf(RichEditor::class, $contentComponent);
    }

    /**
     * 6. Admin tạo Article với 8 fields.
     * 7. Admin chỉnh sửa Article.
     * 8. Category relationship đúng.
     * 9. Author relationship đúng.
     */
    public function test_03_admin_crud_article_with_8_fields_and_relationships(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->create(['name' => 'Thích Nữ Trí Hải']);
        $category = ArticleCategory::factory()->create([
            'name' => 'Thiền Tập Hằng Ngày',
            'slug' => 'thien-tap-hang-ngay',
        ]);

        $this->actingAs($admin);

        // Create article using only 8 fields (no excerpt, no meta_*, no tags)
        $article = Article::create([
            'title' => 'An Lạc Từng Phút Giây',
            'slug' => 'an-lac-tung-phut-giay',
            'article_category_id' => $category->id,
            'author_id' => $author->id,
            'cover_image' => 'articles/covers/an-lac.jpg',
            'status' => 'published',
            'published_at' => now(),
            'content' => '<p>Hơi thở vào tâm tĩnh lặng, hơi thở ra miệng mỉm cười...</p>',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'slug' => 'an-lac-tung-phut-giay',
            'article_category_id' => $category->id,
            'author_id' => $author->id,
            'cover_image' => 'articles/covers/an-lac.jpg',
            'status' => 'published',
        ]);

        // Relationships
        $this->assertEquals('Thiền Tập Hằng Ngày', $article->category->name);
        $this->assertEquals('Thích Nữ Trí Hải', $article->author->name);

        // Edit article
        $article->update([
            'title' => 'An Lạc Từng Phút Giây (Tái Bản)',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'An Lạc Từng Phút Giây (Tái Bản)',
            'status' => 'draft',
        ]);
    }

    /**
     * 10. Cover image upload lưu vào public disk.
     * 11. Cover image hiển thị đúng trong storage.
     */
    public function test_04_cover_image_upload_to_public_disk(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test-cover.jpg', 800, 600);
        $path = $file->store('articles/covers', 'public');

        Storage::disk('public')->assertExists($path);

        $article = Article::factory()->create([
            'cover_image' => $path,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'cover_image' => $path,
        ]);
    }

    /**
     * 12. Invalid image file format validation config.
     */
    public function test_05_cover_image_file_upload_validation_config(): void
    {
        $schema = new Schema;
        ArticleForm::configure($schema);

        /** @var FileUpload $coverComponent */
        $coverComponent = collect($schema->getComponents())->firstWhere(fn ($c) => $c->getName() === 'cover_image');

        $this->assertInstanceOf(FileUpload::class, $coverComponent);
        $this->assertSame('public', $coverComponent->getDiskName());
        $this->assertSame('articles/covers', $coverComponent->getDirectory());
        $this->assertSame(5120, $coverComponent->getMaxSize());
        $this->assertContains('image/jpeg', $coverComponent->getAcceptedFileTypes());
        $this->assertContains('image/png', $coverComponent->getAcceptedFileTypes());
        $this->assertContains('image/webp', $coverComponent->getAcceptedFileTypes());
    }

    /**
     * 13. Unauthorized users cannot access article admin.
     */
    public function test_06_unauthorized_user_cannot_access_article_admin(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->assertFalse(ArticleResource::canViewAny());
        $this->assertFalse(ArticleResource::canCreate());
        $this->assertFalse(ArticleCategoryResource::canViewAny());
        $this->assertFalse(ArticleTagResource::canViewAny());
    }

    /**
     * 14. Article có ảnh hiển thị đúng qua Public API.
     * 15. Article cũ dùng URL ảnh vẫn hiển thị.
     * 16. Draft không public.
     * 17. Published Article hiển thị.
     * 18. Rich Editor content lưu và đọc đúng.
     * 19. Search vẫn hoạt động.
     * 20. Favorites không bị ảnh hưởng.
     */
    public function test_07_public_api_and_compatibility(): void
    {
        $category = ArticleCategory::factory()->create(['name' => 'Phật Pháp Ứng Dụng']);

        // Article 1: Published with uploaded image path & rich content
        $publishedArticle = Article::create([
            'title' => 'Tâm Tĩnh Lặng Giữa Đời Thường',
            'slug' => 'tam-tinh-lang-giua-doi-thuong',
            'article_category_id' => $category->id,
            'cover_image' => 'articles/covers/tam-tinh-lang.jpg',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'content' => '<p>Thực tập chánh niệm mỗi ngày giúp tâm hồn luôn an lạc...</p>',
        ]);

        // Article 2: Published with legacy full URL image
        $legacyArticle = Article::create([
            'title' => 'Bài Học Từ Đức Thế Tôn',
            'slug' => 'bai-hoc-tu-duc-the-ton',
            'article_category_id' => $category->id,
            'cover_image' => 'https://images.unsplash.com/photo-1544717305-2782549b5136',
            'status' => 'published',
            'published_at' => now()->subHours(2),
            'content' => '<p>Từ bi và trí tuệ là hai cánh của con chim giác ngộ...</p>',
        ]);

        // Article 3: Draft (must NOT be public)
        $draftArticle = Article::create([
            'title' => 'Bản Nháp Chưa Xuất Bản',
            'slug' => 'ban-nhap-chua-xuat-ban',
            'status' => 'draft',
            'content' => '<p>Nội dung đang viết...</p>',
        ]);

        // Test Public List API
        $response = $this->getJson('/api/v1/articles');
        $response->assertStatus(200);

        $data = $response->json('data');
        $slugs = collect($data)->pluck('slug')->all();

        $this->assertContains('tam-tinh-lang-giua-doi-thuong', $slugs);
        $this->assertContains('bai-hoc-tu-duc-the-ton', $slugs);
        $this->assertNotContains('ban-nhap-chua-xuat-ban', $slugs);

        // Check synthesized excerpt and cover_image
        $found = collect($data)->firstWhere('slug', 'tam-tinh-lang-giua-doi-thuong');
        $this->assertSame('articles/covers/tam-tinh-lang.jpg', $found['cover_image']);
        $this->assertNotEmpty($found['excerpt']); // synthesized from content!

        $legacyFound = collect($data)->firstWhere('slug', 'bai-hoc-tu-duc-the-ton');
        $this->assertSame('https://images.unsplash.com/photo-1544717305-2782549b5136', $legacyFound['cover_image']);

        // Test Public Detail API
        $detailRes = $this->getJson('/api/v1/articles/tam-tinh-lang-giua-doi-thuong');
        $detailRes->assertStatus(200)
            ->assertJsonPath('data.title', 'Tâm Tĩnh Lặng Giữa Đời Thường')
            ->assertJsonPath('data.cover_image', 'articles/covers/tam-tinh-lang.jpg')
            ->assertJsonPath('data.category.name', 'Phật Pháp Ứng Dụng')
            ->assertJsonPath('data.meta_title', 'Tâm Tĩnh Lặng Giữa Đời Thường');

        // Test Draft cannot be accessed on public detail API
        $draftRes = $this->getJson('/api/v1/articles/ban-nhap-chua-xuat-ban');
        $draftRes->assertStatus(404);

        // Test Favorites compatibility
        $user = User::factory()->create();
        $favorite = UserFavorite::create([
            'user_id' => $user->id,
            'favoritable_type' => Article::class,
            'favoritable_id' => $publishedArticle->id,
        ]);
        $this->assertDatabaseHas('user_favorites', [
            'id' => $favorite->id,
            'favoritable_id' => $publishedArticle->id,
        ]);

        // Test Search includes published article
        $searchRes = $this->getJson('/api/v1/search?q=tam tinh lang&type=article');
        $searchRes->assertStatus(200);
        $searchSlugs = collect($searchRes->json('data'))->pluck('slug')->all();
        $this->assertContains('tam-tinh-lang-giua-doi-thuong', $searchSlugs);
    }

    /**
     * 21. Livewire CreateArticle and EditArticle pages execution.
     */
    public function test_08_livewire_create_and_edit_article_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $cat = ArticleCategory::factory()->create(['name' => 'Khai Thị Thiền Môn']);

        // Test CreateArticle page
        $lw = Livewire::test(CreateArticle::class)
            ->set('data.title', 'Bài Học Tỉnh Thức')
            ->set('data.slug', 'bai-hoc-tinh-thuc')
            ->set('data.article_category_id', $cat->id)
            ->set('data.author_id', $admin->id)
            ->set('data.status', 'published')
            ->set('data.published_at', now()->toDateTimeString())
            ->set('data.content', '<p>Sống trong hiện tại là con đường an vui nhất.</p>')
            ->call('create');

        $this->assertTrue($lw->errors()->isEmpty());
        $this->assertDatabaseHas('articles', [
            'slug' => 'bai-hoc-tinh-thuc',
            'title' => 'Bài Học Tỉnh Thức',
            'status' => 'published',
        ]);

        $article = Article::where('slug', 'bai-hoc-tinh-thuc')->firstOrFail();

        // Test EditArticle page
        $lwEdit = Livewire::test(EditArticle::class, ['record' => $article->id])
            ->set('data.title', 'Bài Học Tỉnh Thức (Đã Cập Nhật)')
            ->call('save');

        $this->assertTrue($lwEdit->errors()->isEmpty());
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Bài Học Tỉnh Thức (Đã Cập Nhật)',
        ]);
    }
}
