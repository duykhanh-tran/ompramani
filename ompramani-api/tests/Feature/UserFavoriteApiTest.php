<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoEditorialStatus;
use App\Enums\VideoEmbedPermission;
use App\Enums\VideoProvider;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\BuddhistVideo;
use App\Models\PracticeSession;
use App\Models\Sutra;
use App\Models\SutraBookmark;
use App\Models\SutraReadingProgress;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\Tradition;
use App\Models\User;
use App\Models\UserFavorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserFavoriteApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected Sutra $publishedSutra;

    protected Sutra $draftSutra;

    protected Article $publishedArticle;

    protected Article $draftArticle;

    protected BuddhistVideo $publishedVideo;

    protected BuddhistVideo $draftVideo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create([
            'name' => 'Phật tử Chân Nguyên',
            'email' => 'channguyen@example.com',
        ]);

        $this->user2 = User::factory()->create([
            'name' => 'Phật tử Diệu Tâm',
            'email' => 'dieutam@example.com',
        ]);

        // 1. Sutra fixtures
        $tradition = Tradition::factory()->create([
            'name' => 'Bắc Truyền',
            'slug' => 'bac-truyen',
            'is_active' => true,
        ]);

        $this->publishedSutra = Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Bát Nhã Ba La Mật Đa',
            'slug' => 'kinh-bat-nha',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->draftSutra = Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Bản Nháp Chưa Duyệt',
            'slug' => 'kinh-ban-nhap',
            'status' => 'draft',
            'published_at' => null,
        ]);

        // 2. Article fixtures
        $category = ArticleCategory::create([
            'name' => 'Phật Pháp Ứng Dụng',
            'slug' => 'phat-phap-ung-dung',
            'is_active' => true,
        ]);

        $this->publishedArticle = Article::create([
            'author_id' => $this->user1->id,
            'article_category_id' => $category->id,
            'title' => 'Ý Nghĩa Của Tứ Diệu Đế',
            'slug' => 'y-nghia-tu-dieu-de',
            'excerpt' => 'Tìm hiểu cốt lõi giáo lý Tứ Diệu Đế trong đạo Phật.',
            'content' => 'Nội dung chi tiết bài viết...',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->draftArticle = Article::create([
            'author_id' => $this->user1->id,
            'article_category_id' => $category->id,
            'title' => 'Bài Viết Bản Nháp',
            'slug' => 'bai-viet-ban-nhap',
            'excerpt' => 'Bản nháp...',
            'content' => 'Nội dung nháp...',
            'status' => 'draft',
            'published_at' => null,
        ]);

        // 3. Video fixtures
        $this->publishedVideo = BuddhistVideo::create([
            'title' => 'Khơi Nguồn Tuệ Giác',
            'slug' => 'khoi-nguon-tue-giac',
            'category' => 'dharma_talk',
            'provider' => VideoProvider::YouTube,
            'provider_video_id' => 'dQw4w9WgXcQ',
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'source_channel_name' => 'Làng Mai',
            'speaker_name' => 'Thiền sư Thích Nhất Hạnh',
            'duration_seconds' => 1800,
            'copyright_status' => VideoCopyrightStatus::VerifiedEmbeddable,
            'embed_permission' => VideoEmbedPermission::Approved,
            'editorial_status' => VideoEditorialStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $this->draftVideo = BuddhistVideo::create([
            'title' => 'Video Nháp Chưa Xuất Bản',
            'slug' => 'video-nhap',
            'category' => 'other',
            'provider' => VideoProvider::YouTube,
            'provider_video_id' => 'abcde123456',
            'canonical_source_url' => 'https://www.youtube.com/watch?v=abcde123456',
            'source_channel_name' => 'Kênh Thử',
            'editorial_status' => VideoEditorialStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function test_guest_cannot_list_favorites(): void
    {
        $response = $this->getJson('/api/v1/me/favorites');

        $response->assertStatus(401);
    }

    public function test_guest_cannot_add_favorite(): void
    {
        $response = $this->putJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}");

        $response->assertStatus(401);
    }

    public function test_guest_cannot_delete_favorite(): void
    {
        $response = $this->deleteJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}");

        $response->assertStatus(401);
    }

    public function test_user_can_favorite_published_sutra(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->putJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_favorited', true)
            ->assertJsonPath('data.type', 'sutra')
            ->assertJsonPath('data.content_public_id', $this->publishedSutra->slug);

        $this->assertDatabaseHas('user_favorites', [
            'user_id' => $this->user1->id,
            'favoritable_type' => 'sutra',
            'favoritable_id' => $this->publishedSutra->id,
        ]);
    }

    public function test_user_can_favorite_published_article(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->putJson("/api/v1/me/favorites/article/{$this->publishedArticle->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_favorited', true)
            ->assertJsonPath('data.type', 'article');

        $this->assertDatabaseHas('user_favorites', [
            'user_id' => $this->user1->id,
            'favoritable_type' => 'article',
            'favoritable_id' => $this->publishedArticle->id,
        ]);
    }

    public function test_audio_favorite_is_no_longer_supported(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->putJson('/api/v1/me/favorites/audio/chu-dai-bi');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Loại nội dung yêu thích không được hỗ trợ.');
    }

    public function test_user_can_favorite_published_video(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->putJson("/api/v1/me/favorites/video/{$this->publishedVideo->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_favorited', true)
            ->assertJsonPath('data.type', 'video');

        $this->assertDatabaseHas('user_favorites', [
            'user_id' => $this->user1->id,
            'favoritable_type' => 'video',
            'favoritable_id' => $this->publishedVideo->id,
        ]);
    }

    public function test_unsupported_content_type_rejected(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->putJson('/api/v1/me/favorites/unknown_type/some-slug');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_nonexistent_or_draft_content_rejected(): void
    {
        Sanctum::actingAs($this->user1);

        // Nonexistent
        $resNotFound = $this->putJson('/api/v1/me/favorites/sutra/non-existent-sutra');
        $resNotFound->assertStatus(404);

        // Draft Sutra
        $resDraftSutra = $this->putJson("/api/v1/me/favorites/sutra/{$this->draftSutra->slug}");
        $resDraftSutra->assertStatus(404);

        // Draft Article
        $resDraftArticle = $this->putJson("/api/v1/me/favorites/article/{$this->draftArticle->slug}");
        $resDraftArticle->assertStatus(404);

        // Draft Video
        $resDraftVideo = $this->putJson("/api/v1/me/favorites/video/{$this->draftVideo->slug}");
        $resDraftVideo->assertStatus(404);
    }

    public function test_duplicate_add_is_idempotent(): void
    {
        Sanctum::actingAs($this->user1);

        $res1 = $this->putJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}");
        $res1->assertStatus(200);

        $res2 = $this->putJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}");
        $res2->assertStatus(200);

        $count = UserFavorite::where('user_id', $this->user1->id)
            ->where('favoritable_type', 'sutra')
            ->where('favoritable_id', $this->publishedSutra->id)
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_remove_favorite_is_idempotent(): void
    {
        Sanctum::actingAs($this->user1);

        // First add
        $this->putJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}")->assertStatus(200);

        // Remove
        $resDelete1 = $this->deleteJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}");
        $resDelete1->assertStatus(200)
            ->assertJsonPath('data.is_favorited', false);

        $this->assertDatabaseMissing('user_favorites', [
            'user_id' => $this->user1->id,
            'favoritable_type' => 'sutra',
            'favoritable_id' => $this->publishedSutra->id,
        ]);

        // Remove again (idempotent)
        $resDelete2 = $this->deleteJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}");
        $resDelete2->assertStatus(200)
            ->assertJsonPath('data.is_favorited', false);
    }

    public function test_user_a_cannot_access_or_modify_user_b_favorites(): void
    {
        // User 1 adds favorite
        Sanctum::actingAs($this->user1);
        $this->putJson("/api/v1/me/favorites/article/{$this->publishedArticle->slug}")->assertStatus(200);

        // User 2 lists favorites
        Sanctum::actingAs($this->user2);
        $listRes = $this->getJson('/api/v1/me/favorites');
        $listRes->assertStatus(200);
        $this->assertCount(0, $listRes->json('data'));

        // User 2 deletes User 1's favorite - User 1's record must not be touched
        $this->deleteJson("/api/v1/me/favorites/article/{$this->publishedArticle->slug}")->assertStatus(200);

        $this->assertDatabaseHas('user_favorites', [
            'user_id' => $this->user1->id,
            'favoritable_type' => 'article',
            'favoritable_id' => $this->publishedArticle->id,
        ]);
    }

    public function test_favorites_list_filter_and_pagination(): void
    {
        Sanctum::actingAs($this->user1);

        $this->putJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}");
        $this->putJson("/api/v1/me/favorites/article/{$this->publishedArticle->slug}");
        $this->putJson("/api/v1/me/favorites/video/{$this->publishedVideo->slug}");

        // Filter all
        $allRes = $this->getJson('/api/v1/me/favorites?type=all');
        $allRes->assertStatus(200);
        $this->assertCount(3, $allRes->json('data'));

        // Filter video only
        $videoRes = $this->getJson('/api/v1/me/favorites?type=video');
        $videoRes->assertStatus(200);
        $this->assertCount(1, $videoRes->json('data'));
        $this->assertSame('video', $videoRes->json('data.0.type'));
        $this->assertSame($this->publishedVideo->slug, $videoRes->json('data.0.content_public_id'));

        // Pagination
        $pageRes = $this->getJson('/api/v1/me/favorites?per_page=2');
        $pageRes->assertStatus(200);
        $this->assertCount(2, $pageRes->json('data'));
        $this->assertSame(3, $pageRes->json('meta.total'));
    }

    public function test_status_endpoint_single_and_batch(): void
    {
        Sanctum::actingAs($this->user1);

        $this->putJson("/api/v1/me/favorites/video/{$this->publishedVideo->slug}");

        // Single check
        $singleRes = $this->getJson("/api/v1/me/favorites/status?type=video&id={$this->publishedVideo->slug}");
        $singleRes->assertStatus(200)
            ->assertJsonPath('data.is_favorited', true);

        $notFavRes = $this->getJson('/api/v1/me/favorites/status?type=video&id=not-favorited-slug');
        $notFavRes->assertStatus(200)
            ->assertJsonPath('data.is_favorited', false);

        // Batch check
        $batchRes = $this->getJson("/api/v1/me/favorites/status?type=video&ids[]={$this->publishedVideo->slug}&ids[]=other-slug");
        $batchRes->assertStatus(200)
            ->assertJsonPath("data.statuses.{$this->publishedVideo->slug}", true)
            ->assertJsonPath('data.statuses.other-slug', false);

        // Max 50 validation
        $tooMany = array_map(fn ($i) => "id-{$i}", range(1, 51));
        $queryString = http_build_query(['type' => 'video', 'ids' => $tooMany]);
        $failRes = $this->getJson("/api/v1/me/favorites/status?{$queryString}");
        $failRes->assertStatus(422);
    }

    public function test_unpublished_saved_content_stays_private(): void
    {
        Sanctum::actingAs($this->user1);

        $this->putJson("/api/v1/me/favorites/article/{$this->publishedArticle->slug}");

        // Unpublish the article
        $this->publishedArticle->update(['status' => 'draft']);

        $res = $this->getJson('/api/v1/me/favorites?type=article');
        $res->assertStatus(200);

        $item = $res->json('data.0');
        $this->assertFalse($item['is_available']);
        $this->assertSame('Nội dung hiện không khả dụng', $item['title']);
        $this->assertNull($item['short_description']);
        $this->assertSame('#', $item['content_url']);
    }

    public function test_favorite_does_not_create_practice_session_or_affect_streak(): void
    {
        Sanctum::actingAs($this->user1);

        $sessionCountBefore = PracticeSession::where('user_id', $this->user1->id)->count();

        $this->putJson("/api/v1/me/favorites/video/{$this->publishedVideo->slug}")->assertStatus(200);

        $sessionCountAfter = PracticeSession::where('user_id', $this->user1->id)->count();

        $this->assertSame($sessionCountBefore, $sessionCountAfter);
    }

    public function test_task24_regression_favorite_does_not_break_bookmarks_or_progress(): void
    {
        Sanctum::actingAs($this->user1);

        $version = SutraVersion::factory()->create([
            'sutra_id' => $this->publishedSutra->id,
            'status' => 'published',
        ]);

        $section = SutraSection::create([
            'sutra_version_id' => $version->id,
            'title' => 'Phẩm 1: Pháp Hội Nhân Duyên',
            'section_order' => 1,
            'content' => 'Như thị ngã văn...',
        ]);

        // 1. Create a section bookmark (Task 24)
        $sectionBookmark = SutraBookmark::create([
            'user_id' => $this->user1->id,
            'type' => 'section',
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $version->id,
            'sutra_section_id' => $section->id,
        ]);

        // 2. Create reading progress (Task 24)
        $progress = SutraReadingProgress::create([
            'user_id' => $this->user1->id,
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $version->id,
            'sutra_section_id' => $section->id,
            'revision' => 1,
            'last_read_at' => now(),
        ]);

        // 3. User favorites the Sutra via Task 42 API
        $this->putJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}")->assertStatus(200);

        // Section bookmark still exists intact!
        $this->assertDatabaseHas('sutra_bookmarks', [
            'id' => $sectionBookmark->id,
            'type' => 'section',
            'sutra_section_id' => $section->id,
        ]);

        // Reading progress still exists intact!
        $this->assertDatabaseHas('sutra_reading_progress', [
            'id' => $progress->id,
            'sutra_section_id' => $section->id,
        ]);

        // 4. User unfavorites the Sutra via Task 42 API
        $this->deleteJson("/api/v1/me/favorites/sutra/{$this->publishedSutra->slug}")->assertStatus(200);

        // Section bookmark MUST NOT be deleted!
        $this->assertDatabaseHas('sutra_bookmarks', [
            'id' => $sectionBookmark->id,
            'type' => 'section',
            'sutra_section_id' => $section->id,
        ]);

        // Reading progress MUST NOT be reset!
        $this->assertDatabaseHas('sutra_reading_progress', [
            'id' => $progress->id,
            'sutra_section_id' => $section->id,
        ]);
    }
}
