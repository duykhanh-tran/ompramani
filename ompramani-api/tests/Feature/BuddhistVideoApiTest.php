<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\VideoCategory;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoEditorialStatus;
use App\Enums\VideoEmbedPermission;
use App\Enums\VideoProvider;
use App\Models\BuddhistVideo;
use App\Models\VideoTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuddhistVideoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createPublishedVideo(array $attributes = []): BuddhistVideo
    {
        return BuddhistVideo::create(array_merge([
            'title' => 'Lời Phật Dạy Về Bình An Nội Tâm',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'provider_video_id' => 'dQw4w9WgXcQ',
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'speaker_name' => 'Thầy Thích Nhất Hạnh',
            'source_channel_name' => 'Làng Mai Official',
            'duration_seconds' => 3600,
            'editorial_status' => VideoEditorialStatus::Published,
            'copyright_status' => VideoCopyrightStatus::VerifiedEmbeddable,
            'embed_permission' => VideoEmbedPermission::Approved,
            'published_at' => now()->subDay(),
        ], $attributes));
    }

    public function test_guest_can_list_published_videos_only(): void
    {
        $published = $this->createPublishedVideo(['title' => 'Video Đã Xuất Bản']);

        BuddhistVideo::create([
            'title' => 'Video Bản Nháp',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXc1',
            'editorial_status' => VideoEditorialStatus::Draft,
        ]);

        BuddhistVideo::create([
            'title' => 'Video Đang Duyệt',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXc2',
            'editorial_status' => VideoEditorialStatus::InReview,
        ]);

        BuddhistVideo::create([
            'title' => 'Video Lưu Trữ',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXc3',
            'editorial_status' => VideoEditorialStatus::Archived,
        ]);

        $res = $this->getJson('/api/v1/videos');

        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $published->slug)
            ->assertJsonPath('data.0.title', 'Video Đã Xuất Bản');
    }

    public function test_accent_insensitive_search_matches(): void
    {
        $this->createPublishedVideo(['title' => 'Kinh Bát Đại Nhân Giác']);
        $this->createPublishedVideo(['title' => 'Pháp Thoại Chuyển Hóa Cơn Giận']);

        // Search with diacritics: "Bát Đại"
        $res1 = $this->getJson('/api/v1/videos?q=Bát Đại');
        $res1->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Kinh Bát Đại Nhân Giác');

        // Search without diacritics: "bat dai"
        $res2 = $this->getJson('/api/v1/videos?q=bat dai');
        $res2->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Kinh Bát Đại Nhân Giác');

        // Search speaker without diacritics: "nhat hanh"
        $res3 = $this->getJson('/api/v1/videos?q=nhat hanh');
        $res3->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filter_by_category(): void
    {
        $v1 = $this->createPublishedVideo([
            'title' => 'Pháp Thoại 1',
            'category' => VideoCategory::DharmaTalk,
        ]);

        $v2 = $this->createPublishedVideo([
            'title' => 'Hướng Dẫn Thiền 1',
            'category' => VideoCategory::MeditationGuidance,
        ]);

        $res = $this->getJson('/api/v1/videos?category=meditation_guidance');

        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $v2->slug);
    }

    public function test_filter_by_topic(): void
    {
        $v1 = $this->createPublishedVideo(['title' => 'Video Có Topic']);
        $v2 = $this->createPublishedVideo(['title' => 'Video Khác']);

        $topic = VideoTopic::create(['name' => 'Tứ Diệu Đế', 'slug' => 'tu-dieu-de']);
        $v1->topics()->attach($topic->id);

        $res = $this->getJson('/api/v1/videos?topic=tu-dieu-de');

        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $v1->slug);
    }

    public function test_filter_by_speaker(): void
    {
        $v1 = $this->createPublishedVideo([
            'title' => 'Bài Giảng 1',
            'speaker_name' => 'HT. Thích Thanh Từ',
        ]);

        $v2 = $this->createPublishedVideo([
            'title' => 'Bài Giảng 2',
            'speaker_name' => 'TT. Thích Trí Siêu',
        ]);

        $res = $this->getJson('/api/v1/videos?speaker=Thanh Từ');

        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $v1->slug);
    }

    public function test_guest_can_view_published_video_detail(): void
    {
        $video = $this->createPublishedVideo([
            'title' => 'Sống Trong Hiện Tại',
            'short_description' => 'Tóm tắt bài pháp thoại.',
            'description' => 'Mô tả chi tiết và lược dẫn bài giảng tại tu viện.',
        ]);

        $res = $this->getJson("/api/v1/videos/{$video->slug}");

        $res->assertOk()
            ->assertJsonPath('data.slug', $video->slug)
            ->assertJsonPath('data.title', 'Sống Trong Hiện Tại')
            ->assertJsonPath('data.playback_mode', 'approved_embed')
            ->assertJsonPath('data.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
            ->assertJsonPath('data.duration_formatted', '01:00:00');
    }

    public function test_draft_or_deleted_video_returns_404(): void
    {
        $draft = BuddhistVideo::create([
            'title' => 'Video Chưa Duyệt',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'editorial_status' => VideoEditorialStatus::Draft,
        ]);

        $this->getJson("/api/v1/videos/{$draft->slug}")->assertNotFound();

        $published = $this->createPublishedVideo(['title' => 'Video Bị Xóa']);
        $slug = $published->slug;
        $published->delete();

        $this->getJson("/api/v1/videos/{$slug}")->assertNotFound();
    }

    public function test_categories_endpoint_returns_registry(): void
    {
        $res = $this->getJson('/api/v1/videos/categories');

        $res->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['key', 'label'],
                ],
            ]);
    }

    public function test_topics_endpoint_returns_active_topics(): void
    {
        VideoTopic::create(['name' => 'Chánh Niệm', 'slug' => 'chanh-niem', 'is_active' => true]);
        VideoTopic::create(['name' => 'Ẩn Topic', 'slug' => 'an-topic', 'is_active' => false]);

        $res = $this->getJson('/api/v1/videos/topics');

        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'chanh-niem');
    }

    public function test_get_video_is_idempotent_with_no_db_writes(): void
    {
        $video = $this->createPublishedVideo();
        $slug = $video->slug;

        $countBefore = BuddhistVideo::count();
        $this->getJson("/api/v1/videos/{$slug}")->assertOk();
        $countAfter = BuddhistVideo::count();

        $this->assertSame($countBefore, $countAfter);
    }
}
