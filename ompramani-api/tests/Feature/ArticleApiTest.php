<?php

namespace Tests\Feature;

use App\Filament\Resources\ArticleCategories\ArticleCategoryResource;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\ArticleTags\ArticleTagResource;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleRelation;
use App\Models\ArticleTag;
use App\Models\BuddhistFigure;
use App\Models\Mantra;
use App\Models\Sutra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Admin tạo Article qua Filament.
     */
    public function test_01_admin_can_crud_article(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);
        $this->assertTrue(ArticleResource::canViewAny());
        $this->assertTrue(ArticleResource::canCreate());

        $category = ArticleCategory::factory()->create();
        $article = Article::factory()->create([
            'article_category_id' => $category->id,
            'title' => 'Ý Nghĩa Niệm Phật',
            'slug' => 'y-nghia-niem-phat',
            'status' => 'published',
        ]);

        $this->assertTrue(ArticleResource::canEdit($article));
        $this->assertTrue(ArticleResource::canDelete($article));
        $this->assertDatabaseHas('articles', ['slug' => 'y-nghia-niem-phat']);
    }

    /**
     * 2. Admin sửa Article.
     */
    public function test_02_admin_can_edit_article(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $article = Article::factory()->create([
            'title' => 'Tiêu đề cũ',
            'status' => 'draft',
        ]);

        $article->update([
            'title' => 'Tiêu đề mới',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Tiêu đề mới',
            'status' => 'published',
        ]);
    }

    /**
     * 3. Admin tạo Category.
     */
    public function test_03_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->assertTrue(ArticleCategoryResource::canViewAny());
        $this->assertTrue(ArticleCategoryResource::canCreate());

        $cat = ArticleCategory::factory()->create([
            'name' => 'Phật Học Căn Bản',
            'slug' => 'phat-hoc-can-ban',
        ]);

        $this->assertDatabaseHas('article_categories', ['slug' => 'phat-hoc-can-ban']);
    }

    /**
     * 4. Admin không quản lý Tag qua Filament admin (chỉ giữ ArticleCategories và Articles).
     */
    public function test_04_admin_can_create_tag(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        // Tags are removed from Filament admin navigation and CRUD
        $this->assertFalse(ArticleTagResource::shouldRegisterNavigation());
        $this->assertFalse(ArticleTagResource::canViewAny());
        $this->assertFalse(ArticleTagResource::canCreate());

        // Existing tag records in database remain intact and supported
        $tag = ArticleTag::factory()->create([
            'name' => 'Niệm Phật',
            'slug' => 'niem-phat',
        ]);

        $this->assertDatabaseHas('article_tags', ['slug' => 'niem-phat']);
    }

    /**
     * 5. User thường không truy cập Article management.
     */
    public function test_05_regular_user_cannot_access_article_management(): void
    {
        $regularUser = User::factory()->create(['role' => 'user']);
        $article = Article::factory()->create();

        $this->actingAs($regularUser);
        $this->assertFalse(ArticleResource::canViewAny());
        $this->assertFalse(ArticleResource::canCreate());
        $this->assertFalse(ArticleResource::canEdit($article));
        $this->assertFalse(ArticleResource::canDelete($article));

        $this->assertFalse(ArticleCategoryResource::canViewAny());
        $this->assertFalse(ArticleTagResource::canViewAny());

        $response = $this->get('/admin/articles');
        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    /**
     * 6. Draft Article không public.
     */
    public function test_06_draft_article_is_not_public(): void
    {
        Article::factory()->draft()->create(['slug' => 'bai-viet-nhap']);

        $listRes = $this->getJson('/api/v1/articles');
        $listRes->assertStatus(200);
        $this->assertCount(0, $listRes->json('data'));

        $detailRes = $this->getJson('/api/v1/articles/bai-viet-nhap');
        $detailRes->assertStatus(404);
    }

    /**
     * 7. Review Article không public.
     */
    public function test_07_review_article_is_not_public(): void
    {
        Article::factory()->review()->create(['slug' => 'bai-viet-cho-duyet']);

        $listRes = $this->getJson('/api/v1/articles');
        $listRes->assertStatus(200);
        $this->assertCount(0, $listRes->json('data'));

        $detailRes = $this->getJson('/api/v1/articles/bai-viet-cho-duyet');
        $detailRes->assertStatus(404);
    }

    /**
     * 8. Published Article public.
     */
    public function test_08_published_article_is_public(): void
    {
        Article::factory()->published()->create(['slug' => 'bai-viet-xuat-ban']);

        $listRes = $this->getJson('/api/v1/articles');
        $listRes->assertStatus(200);
        $this->assertCount(1, $listRes->json('data'));

        $detailRes = $this->getJson('/api/v1/articles/bai-viet-xuat-ban');
        $detailRes->assertStatus(200);
    }

    /**
     * 9. Future published_at chưa public.
     */
    public function test_09_future_published_article_is_not_public(): void
    {
        Article::factory()->futurePublished()->create(['slug' => 'bai-viet-tuong-lai']);

        $listRes = $this->getJson('/api/v1/articles');
        $listRes->assertStatus(200);
        $this->assertCount(0, $listRes->json('data'));

        $detailRes = $this->getJson('/api/v1/articles/bai-viet-tuong-lai');
        $detailRes->assertStatus(404);
    }

    /**
     * 10. GET articles trả published với cấu trúc chuẩn.
     */
    public function test_10_list_api_returns_published_articles(): void
    {
        $category = ArticleCategory::factory()->create(['name' => 'Thiền Định', 'slug' => 'thien-dinh']);
        $tag = ArticleTag::factory()->create(['name' => 'Chánh Niệm', 'slug' => 'chanh-niem']);
        $author = User::factory()->create(['name' => 'Thầy Thích Nhất Hạnh']);

        $article = Article::factory()->published()->create([
            'article_category_id' => $category->id,
            'author_id' => $author->id,
            'title' => 'Hơi Thở Vào Hơi Thở Ra',
            'slug' => 'hoi-tho-vao-hoi-tho-ra',
            'excerpt' => 'Thực tập chánh niệm qua hơi thở.',
            'cover_image' => 'covers/meditation.jpg',
        ]);
        $article->tags()->attach($tag);

        $response = $this->getJson('/api/v1/articles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'excerpt',
                        'cover_image',
                        'category' => ['id', 'name', 'slug'],
                        'tags' => [
                            '*' => ['id', 'name', 'slug'],
                        ],
                        'author' => ['id', 'name'],
                        'published_at',
                    ],
                ],
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);

        $this->assertSame('Hơi Thở Vào Hơi Thở Ra', $response->json('data.0.title'));
        $this->assertSame('thien-dinh', $response->json('data.0.category.slug'));
        $this->assertSame('Thầy Thích Nhất Hạnh', $response->json('data.0.author.name'));
    }

    /**
     * 11. Pagination hoạt động.
     */
    public function test_11_pagination_works(): void
    {
        Article::factory()->count(15)->published()->create();

        $resPage1 = $this->getJson('/api/v1/articles?page=1&per_page=5');
        $resPage1->assertStatus(200)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 15);
        $this->assertCount(5, $resPage1->json('data'));

        $resPage2 = $this->getJson('/api/v1/articles?page=2&per_page=5');
        $resPage2->assertStatus(200)
            ->assertJsonPath('meta.current_page', 2);
        $this->assertCount(5, $resPage2->json('data'));

        $this->assertNotSame(
            $resPage1->json('data.0.id'),
            $resPage2->json('data.0.id')
        );
    }

    /**
     * 12. Category filter.
     */
    public function test_12_category_filter(): void
    {
        $cat1 = ArticleCategory::factory()->create(['slug' => 'thien']);
        $cat2 = ArticleCategory::factory()->create(['slug' => 'tinh-do']);

        $art1 = Article::factory()->published()->create(['article_category_id' => $cat1->id]);
        $art2 = Article::factory()->published()->create(['article_category_id' => $cat2->id]);

        $response = $this->getJson('/api/v1/articles?category=thien');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($art1->id, $response->json('data.0.id'));
    }

    /**
     * 13. Tag filter.
     */
    public function test_13_tag_filter(): void
    {
        $tag1 = ArticleTag::factory()->create(['slug' => 'an-chay']);
        $tag2 = ArticleTag::factory()->create(['slug' => 'phong-sanh']);

        $art1 = Article::factory()->published()->create();
        $art1->tags()->attach($tag1);

        $art2 = Article::factory()->published()->create();
        $art2->tags()->attach($tag2);

        $response = $this->getJson('/api/v1/articles?tag=an-chay');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($art1->id, $response->json('data.0.id'));
    }

    /**
     * 14. Article detail bằng slug.
     */
    public function test_14_article_detail_by_slug(): void
    {
        $article = Article::factory()->published()->create([
            'title' => 'Ý Nghĩa Niệm Phật',
            'slug' => 'y-nghia-niem-phat',
            'content' => '<h1>Niệm Phật</h1><p>Nội dung chi tiết bài viết.</p>',
        ]);

        $response = $this->getJson('/api/v1/articles/y-nghia-niem-phat');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $article->id)
            ->assertJsonPath('data.title', 'Ý Nghĩa Niệm Phật')
            ->assertJsonPath('data.slug', 'y-nghia-niem-phat');
    }

    /**
     * 15. Slug không tồn tại → 404.
     */
    public function test_15_slug_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/v1/articles/khong-ton-tai');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Không tìm thấy nội dung.']);
    }

    /**
     * 16. List không chứa full content.
     */
    public function test_16_list_does_not_contain_full_content(): void
    {
        Article::factory()->published()->create([
            'content' => 'Rất dài dòng không nên xuất hiện ở list view...',
        ]);

        $response = $this->getJson('/api/v1/articles');

        $response->assertStatus(200);
        $firstItem = $response->json('data.0');
        $this->assertArrayNotHasKey('content', $firstItem);
        $this->assertArrayHasKey('excerpt', $firstItem);
    }

    /**
     * 17. Detail chứa content và SEO metadata fallback.
     */
    public function test_17_detail_contains_full_content(): void
    {
        $article = Article::factory()->published()->create([
            'title' => 'Bài Pháp Thâm Sâu',
            'slug' => 'bai-phap-tham-sau',
            'excerpt' => 'Tóm tắt bài pháp.',
            'content' => '<p>Chính văn bài viết đầy đủ.</p>',
            'meta_title' => null,
            'meta_description' => null,
        ]);

        $response = $this->getJson('/api/v1/articles/bai-phap-tham-sau');

        $response->assertStatus(200)
            ->assertJsonPath('data.content', '<p>Chính văn bài viết đầy đủ.</p>')
            ->assertJsonPath('data.meta_title', 'Bài Pháp Thâm Sâu')
            ->assertJsonPath('data.meta_description', 'Tóm tắt bài pháp.');
    }

    /**
     * 18. Categories API chỉ trả active và sắp xếp đúng.
     */
    public function test_18_categories_api_only_returns_active(): void
    {
        $active1 = ArticleCategory::factory()->create([
            'name' => 'B. Thiền Học',
            'slug' => 'thien-hoc',
            'is_active' => true,
            'position' => 2,
        ]);

        $active2 = ArticleCategory::factory()->create([
            'name' => 'A. Phật Học Cơ Bản',
            'slug' => 'phat-hoc-co-ban',
            'is_active' => true,
            'position' => 1,
        ]);

        ArticleCategory::factory()->inactive()->create([
            'name' => 'Ẩn Danh',
            'slug' => 'an-danh',
        ]);

        // Publish 2 articles in active2
        Article::factory()->count(2)->published()->create(['article_category_id' => $active2->id]);

        $response = $this->getJson('/api/v1/article-categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'description', 'position', 'published_articles_count'],
                ],
            ]);

        $categories = $response->json('data');
        $this->assertCount(2, $categories);
        $this->assertSame('phat-hoc-co-ban', $categories[0]['slug']);
        $this->assertSame(2, $categories[0]['published_articles_count']);
        $this->assertSame('thien-hoc', $categories[1]['slug']);
    }

    /**
     * 19. Related Buddhist content chỉ trả published.
     */
    public function test_19_related_buddhist_content_only_returns_published(): void
    {
        $article = Article::factory()->published()->create(['slug' => 'phap-mon-niem-phat']);

        // 1. Published Sutra
        $pubSutra = Sutra::factory()->published()->create([
            'title' => 'Kinh A Di Đà',
            'slug' => 'kinh-a-di-da',
        ]);
        ArticleRelation::create([
            'article_id' => $article->id,
            'related_type' => 'sutra',
            'related_id' => $pubSutra->id,
            'position' => 1,
        ]);

        // 2. Draft Sutra
        $draftSutra = Sutra::factory()->draft()->create(['title' => 'Kinh Nháp']);
        ArticleRelation::create([
            'article_id' => $article->id,
            'related_type' => 'sutra',
            'related_id' => $draftSutra->id,
            'position' => 2,
        ]);

        // 3. Published Figure
        $pubFig = BuddhistFigure::factory()->published()->create([
            'name' => 'A Di Đà Phật',
            'slug' => 'a-di-da-phat',
        ]);
        ArticleRelation::create([
            'article_id' => $article->id,
            'related_type' => 'figure',
            'related_id' => $pubFig->id,
            'position' => 3,
        ]);

        // 4. Published Mantra
        $pubMantra = Mantra::factory()->published()->create([
            'title' => 'Chú Vãng Sanh',
            'slug' => 'chu-vang-sanh',
        ]);
        ArticleRelation::create([
            'article_id' => $article->id,
            'related_type' => 'mantra',
            'related_id' => $pubMantra->id,
            'position' => 4,
        ]);

        $response = $this->getJson('/api/v1/articles/phap-mon-niem-phat');

        $response->assertStatus(200);
        $relatedContent = $response->json('data.related_content');

        // Only 3 published items returned, draft excluded
        $this->assertCount(3, $relatedContent);

        $slugs = collect($relatedContent)->pluck('slug')->all();
        $this->assertContains('kinh-a-di-da', $slugs);
        $this->assertContains('a-di-da-phat', $slugs);
        $this->assertContains('chu-vang-sanh', $slugs);
        $this->assertNotContains($draftSutra->slug, $slugs);
    }

    /**
     * 20. Related articles không trả draft.
     */
    public function test_20_related_articles_do_not_return_draft(): void
    {
        $category = ArticleCategory::factory()->create();

        $mainArticle = Article::factory()->published()->create([
            'article_category_id' => $category->id,
            'slug' => 'bai-chinh',
        ]);

        $pubRelated = Article::factory()->published()->create([
            'article_category_id' => $category->id,
            'title' => 'Bài liên quan xuất bản',
            'slug' => 'bai-lien-quan-xuat-ban',
        ]);

        $draftRelated = Article::factory()->draft()->create([
            'article_category_id' => $category->id,
            'title' => 'Bài liên quan nháp',
            'slug' => 'bai-lien-quan-nhap',
        ]);

        $response = $this->getJson('/api/v1/articles/bai-chinh');

        $response->assertStatus(200);
        $related = $response->json('data.related_articles');

        $this->assertCount(1, $related);
        $this->assertSame('bai-lien-quan-xuat-ban', $related[0]['slug']);
    }

    /**
     * 21. per_page vượt giới hạn bị từ chối.
     */
    public function test_21_per_page_over_limit_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/articles?per_page=100');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    /**
     * 22. Search Article title.
     */
    public function test_22_search_article_title(): void
    {
        Article::factory()->published()->create([
            'title' => 'Ý Nghĩa Niệm Phật',
            'slug' => 'y-nghia-niem-phat',
        ]);

        $response = $this->getJson('/api/v1/search?q=Y Nghia Niem Phat&type=article');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
        $item = collect($response->json('data'))->firstWhere('slug', 'y-nghia-niem-phat');
        $this->assertNotNull($item);
        $this->assertSame('article', $item['type']);
        $this->assertSame('Ý Nghĩa Niệm Phật', $item['title']);
    }

    /**
     * 23. Search không dấu.
     */
    public function test_23_search_unaccented(): void
    {
        Article::factory()->published()->create([
            'title' => 'Thiền Quán Hơi Thở',
            'slug' => 'thien-quan-hoi-tho',
        ]);

        $response = $this->getJson('/api/v1/search?q=thien quan hoi tho');

        $response->assertStatus(200);
        $item = collect($response->json('data'))->firstWhere('slug', 'thien-quan-hoi-tho');
        $this->assertNotNull($item);
        $this->assertSame('article', $item['type']);
    }

    /**
     * 24. Search excerpt.
     */
    public function test_24_search_excerpt(): void
    {
        Article::factory()->published()->create([
            'title' => 'Giáo Lý Duyên Khởi',
            'slug' => 'giao-ly-duyen-khoi',
            'excerpt' => 'Thập nhị nhân duyên là cốt tủy giáo lý Phật đà.',
        ]);

        $response = $this->getJson('/api/v1/search?q=Thap nhi nhan duyen');

        $response->assertStatus(200);
        $item = collect($response->json('data'))->firstWhere('slug', 'giao-ly-duyen-khoi');
        $this->assertNotNull($item);
        $this->assertSame('article', $item['type']);
    }

    /**
     * 25. Search content.
     */
    public function test_25_search_content(): void
    {
        Article::factory()->published()->create([
            'title' => 'Lược Sử Phật Giáo',
            'slug' => 'luoc-su-phat-giao',
            'content' => '<p>Đức Phật Thích Ca đản sinh tại vườn Lâm Tỳ Ni dưới cây Vô Ưu.</p>',
        ]);

        $response = $this->getJson('/api/v1/search?q=Lam Ty Ni');

        $response->assertStatus(200);
        $item = collect($response->json('data'))->firstWhere('slug', 'luoc-su-phat-giao');
        $this->assertNotNull($item);
        $this->assertSame('article', $item['type']);
    }

    /**
     * 26. Draft và Future Articles không tìm kiếm được.
     */
    public function test_26_draft_and_future_articles_not_in_search(): void
    {
        Article::factory()->draft()->create([
            'title' => 'Bài Viết Bí Mật Bản Nháp',
            'slug' => 'bi-mat-nhap',
        ]);

        Article::factory()->futurePublished()->create([
            'title' => 'Bài Viết Bí Mật Tương Lai',
            'slug' => 'bi-mat-tuong-lai',
        ]);

        $response = $this->getJson('/api/v1/search?q=Bi Mat');

        $response->assertStatus(200);
        $slugs = collect($response->json('data'))->pluck('slug')->all();
        $this->assertNotContains('bi-mat-nhap', $slugs);
        $this->assertNotContains('bi-mat-tuong-lai', $slugs);
    }

    /**
     * 27. type=article hoạt động.
     */
    public function test_27_type_article_filter(): void
    {
        Article::factory()->published()->create(['title' => 'Bài Viết Thiền', 'slug' => 'bai-viet-thien']);
        Sutra::factory()->published()->create(['title' => 'Kinh Thiền', 'slug' => 'kinh-thien']);

        $resArticle = $this->getJson('/api/v1/search?q=Thien&type=article');
        $resArticle->assertStatus(200);

        foreach ($resArticle->json('data') as $item) {
            $this->assertSame('article', $item['type']);
        }
    }

    /**
     * 28. Search suggestions trả Article.
     */
    public function test_28_search_suggestions_return_article(): void
    {
        Article::factory()->published()->create([
            'title' => 'Hương Vị Thiền Tập',
            'slug' => 'huong-vi-thien-tap',
        ]);

        $response = $this->getJson('/api/v1/search/suggestions?q=huong vi');

        $response->assertStatus(200);
        $suggestions = $response->json('data');
        $item = collect($suggestions)->firstWhere('slug', 'huong-vi-thien-tap');
        $this->assertNotNull($item);
        $this->assertSame('article', $item['type']);
        $this->assertSame('Hương Vị Thiền Tập', $item['title']);
    }

    /**
     * 29. Suggestions không trả draft article.
     */
    public function test_29_suggestions_do_not_return_draft_article(): void
    {
        Article::factory()->draft()->create([
            'title' => 'Tuyệt Mật Bài Viết Nháp',
            'slug' => 'tuyet-mat-bai-viet-nhap',
        ]);

        $response = $this->getJson('/api/v1/search/suggestions?q=tuyet mat');

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertNotContains('Tuyệt Mật Bài Viết Nháp', $titles);
    }

    /**
     * 30. XSS / Security: Không expose email author, không có public write endpoint.
     */
    public function test_30_xss_and_security_no_author_email_leak_and_no_public_write(): void
    {
        $author = User::factory()->create([
            'name' => 'Thích Minh Châu',
            'email' => 'minhchau_secret_private@ompramani.test',
        ]);

        $article = Article::factory()->published()->create([
            'author_id' => $author->id,
            'slug' => 'kinh-nikaya',
            'content' => '<p>Nội dung an toàn.</p>',
        ]);

        $response = $this->getJson('/api/v1/articles/kinh-nikaya');
        $response->assertStatus(200);

        $rawJson = $response->getContent();
        $this->assertStringNotContainsString('minhchau_secret_private@ompramani.test', $rawJson);
        $this->assertStringNotContainsString('password', $rawJson);

        // Verify no public POST/PUT/DELETE routes exist for articles
        $postRes = $this->postJson('/api/v1/articles', ['title' => 'Hacked']);
        $this->assertSame(405, $postRes->status());

        $deleteRes = $this->deleteJson('/api/v1/articles/kinh-nikaya');
        $this->assertSame(405, $deleteRes->status());
    }

    /**
     * 31. Tiêu chí nghiệm thu luồng end-to-end (Section 36).
     */
    public function test_31_end_to_end_acceptance_workflow(): void
    {
        // 1. Category
        $cat = ArticleCategory::factory()->create([
            'name' => 'Phật Học Ứng Dụng',
            'slug' => 'phat-hoc-ung-dung',
        ]);

        // 2. Tag
        $tag = ArticleTag::factory()->create([
            'name' => 'An Lạc',
            'slug' => 'an-lac',
        ]);

        // 3. Related Sutra, Figure, Mantra
        $sutra = Sutra::factory()->published()->create([
            'title' => 'Kinh Phổ Môn',
            'slug' => 'kinh-pho-mon',
        ]);
        $figure = BuddhistFigure::factory()->published()->create([
            'name' => 'Quán Thế Âm Bồ Tát',
            'slug' => 'quan-the-am-bo-tat',
        ]);
        $mantra = Mantra::factory()->published()->create([
            'title' => 'Chú Đại Bi',
            'slug' => 'chu-dai-bi',
        ]);

        // 4. Create Article and attach
        $article = Article::factory()->published()->create([
            'article_category_id' => $cat->id,
            'title' => 'Ứng Dụng Hạnh Nguyện Bồ Tát Quán Thế Âm',
            'slug' => 'ung-dung-hanh-nguyen-bo-tat-quan-the-am',
            'excerpt' => 'Lắng nghe và thấu hiểu cuộc đời.',
            'content' => '<p>Hạnh nguyện của Bồ Tát Quán Thế Âm là hạnh nguyện lắng nghe với tâm từ bi...</p>',
        ]);
        $article->tags()->attach($tag);

        ArticleRelation::create([
            'article_id' => $article->id,
            'related_type' => 'sutra',
            'related_id' => $sutra->id,
            'position' => 1,
        ]);
        ArticleRelation::create([
            'article_id' => $article->id,
            'related_type' => 'figure',
            'related_id' => $figure->id,
            'position' => 2,
        ]);
        ArticleRelation::create([
            'article_id' => $article->id,
            'related_type' => 'mantra',
            'related_id' => $mantra->id,
            'position' => 3,
        ]);

        // 5. Test Public APIs
        // - /api/v1/article-categories
        $resCats = $this->getJson('/api/v1/article-categories');
        $resCats->assertStatus(200);
        $this->assertNotNull(collect($resCats->json('data'))->firstWhere('slug', 'phat-hoc-ung-dung'));

        // - /api/v1/articles
        $resList = $this->getJson('/api/v1/articles');
        $resList->assertStatus(200);
        $this->assertNotNull(collect($resList->json('data'))->firstWhere('slug', 'ung-dung-hanh-nguyen-bo-tat-quan-the-am'));

        // - /api/v1/articles/{slug}
        $resDetail = $this->getJson('/api/v1/articles/ung-dung-hanh-nguyen-bo-tat-quan-the-am');
        $resDetail->assertStatus(200)
            ->assertJsonPath('data.slug', 'ung-dung-hanh-nguyen-bo-tat-quan-the-am')
            ->assertJsonPath('data.category.slug', 'phat-hoc-ung-dung')
            ->assertJsonPath('data.tags.0.slug', 'an-lac');

        $this->assertCount(3, $resDetail->json('data.related_content'));

        // - /api/v1/search (returns simultaneously across sutra, figure, mantra, article)
        $resSearch = $this->getJson('/api/v1/search?q=Quan The Am');
        $resSearch->assertStatus(200);

        $typesInSearch = collect($resSearch->json('data'))->pluck('type')->unique()->all();
        $this->assertContains('figure', $typesInSearch);
        $this->assertContains('article', $typesInSearch);

        // - /api/v1/search/suggestions
        $resSug = $this->getJson('/api/v1/search/suggestions?q=ung dung');
        $resSug->assertStatus(200);
        $this->assertNotNull(collect($resSug->json('data'))->firstWhere('slug', 'ung-dung-hanh-nguyen-bo-tat-quan-the-am'));
    }
}
