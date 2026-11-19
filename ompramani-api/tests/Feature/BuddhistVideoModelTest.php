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
use InvalidArgumentException;
use Tests\TestCase;

class BuddhistVideoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_video_with_unique_slug_and_public_id(): void
    {
        $video = BuddhistVideo::create([
            'title' => 'Ý Nghĩa Vô Thường Trong Đời Sống',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'editorial_status' => VideoEditorialStatus::Draft,
        ]);

        $this->assertNotNull($video->public_id);
        $this->assertSame('y-nghia-vo-thuong-trong-doi-song', $video->slug);
        $this->assertSame('dQw4w9WgXcQ', $video->provider_video_id);
        $this->assertNotNull($video->normalized_title);
    }

    public function test_duplicate_title_generates_disambiguated_slug(): void
    {
        $v1 = BuddhistVideo::create([
            'title' => 'Bát Nhã Tâm Kinh',
            'category' => VideoCategory::SutraStudy,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXc1',
        ]);

        $v2 = BuddhistVideo::create([
            'title' => 'Bát Nhã Tâm Kinh',
            'category' => VideoCategory::SutraStudy,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXc2',
        ]);

        $this->assertSame('bat-nha-tam-kinh', $v1->slug);
        $this->assertSame('bat-nha-tam-kinh-1', $v2->slug);
    }

    public function test_negative_duration_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BuddhistVideo::create([
            'title' => 'Video Thời Lượng Sai',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_seconds' => -10,
        ]);
    }

    public function test_invalid_canonical_url_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BuddhistVideo::create([
            'title' => 'Video URL Sai',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'javascript:alert(1)',
        ]);
    }

    public function test_embed_url_generated_only_when_approved_and_verified(): void
    {
        // 1. External only permission -> embed URL is null
        $v1 = BuddhistVideo::create([
            'title' => 'Video Chỉ Xem Ngoài',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'provider_video_id' => 'dQw4w9WgXcQ',
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'copyright_status' => VideoCopyrightStatus::VerifiedEmbeddable,
            'embed_permission' => VideoEmbedPermission::ExternalOnly,
        ]);
        $this->assertNull($v1->getEmbedUrl());
        $this->assertSame('external_link_only', $v1->getPlaybackMode());

        // 2. Pending review copyright -> embed URL is null
        $v2 = BuddhistVideo::create([
            'title' => 'Video Chưa Kiểm Bản Quyền',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'provider_video_id' => 'dQw4w9WgXcQ',
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'copyright_status' => VideoCopyrightStatus::PendingReview,
            'embed_permission' => VideoEmbedPermission::Approved,
        ]);
        $this->assertNull($v2->getEmbedUrl());
        $this->assertSame('external_link_only', $v2->getPlaybackMode());

        // 3. Approved embed + verified embeddable -> embed URL generated with youtube-nocookie
        $v3 = BuddhistVideo::create([
            'title' => 'Video Đủ Điều Kiện Nhúng',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'provider_video_id' => 'dQw4w9WgXcQ',
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'copyright_status' => VideoCopyrightStatus::VerifiedEmbeddable,
            'embed_permission' => VideoEmbedPermission::Approved,
        ]);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $v3->getEmbedUrl());
        $this->assertSame('approved_embed', $v3->getPlaybackMode());
    }

    public function test_vimeo_embed_url_generation(): void
    {
        $video = BuddhistVideo::create([
            'title' => 'Pháp Thoại Vimeo',
            'category' => VideoCategory::MeditationGuidance,
            'provider' => VideoProvider::Vimeo,
            'provider_video_id' => '76979871',
            'canonical_source_url' => 'https://vimeo.com/76979871',
            'copyright_status' => VideoCopyrightStatus::VerifiedEmbeddable,
            'embed_permission' => VideoEmbedPermission::Approved,
        ]);

        $this->assertSame('https://player.vimeo.com/video/76979871', $video->getEmbedUrl());
    }

    public function test_formatted_duration_semantics(): void
    {
        $v1 = BuddhistVideo::create([
            'title' => 'Video Dài 45 Phút',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_seconds' => 2730, // 45m 30s
        ]);
        $this->assertSame('45:30', $v1->formattedDuration());

        $v2 = BuddhistVideo::create([
            'title' => 'Video Dài Hơn 1 Tiếng',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_seconds' => 3665, // 1h 1m 5s
        ]);
        $this->assertSame('01:01:05', $v2->formattedDuration());

        $v3 = BuddhistVideo::create([
            'title' => 'Video Không Có Thời Lượng',
            'category' => VideoCategory::DharmaTalk,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_seconds' => null,
        ]);
        $this->assertNull($v3->formattedDuration());
    }

    public function test_topics_relationship(): void
    {
        $video = BuddhistVideo::create([
            'title' => 'Thiền Tứ Niệm Xứ',
            'category' => VideoCategory::MeditationGuidance,
            'provider' => VideoProvider::YouTube,
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $topic = VideoTopic::create([
            'name' => 'Thiền Minh Sát (Vipassana)',
            'description' => 'Phương pháp thực hành quan sát thân thọ tâm pháp.',
        ]);

        $video->topics()->attach($topic->id);

        $this->assertCount(1, $video->topics);
        $this->assertSame('thien-minh-sat-vipassana', $video->topics->first()->slug);
    }
}
