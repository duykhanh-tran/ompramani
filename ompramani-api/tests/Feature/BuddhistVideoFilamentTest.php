<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\VideoCategory;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoEditorialStatus;
use App\Enums\VideoEmbedPermission;
use App\Enums\VideoProvider;
use App\Filament\Resources\BuddhistVideos\BuddhistVideoResource;
use App\Filament\Resources\BuddhistVideos\Schemas\BuddhistVideoForm;
use App\Models\BuddhistVideo;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BuddhistVideoFilamentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test form schema contains EXACTLY 3 fields: title, slug, canonical_source_url.
     */
    public function test_form_schema_has_exactly_three_fields(): void
    {
        $schema = BuddhistVideoForm::configure(new Schema());
        $components = $schema->getComponents();

        $this->assertCount(3, $components, 'Form must contain exactly 3 components');

        $fieldNames = array_map(fn ($component) => $component->getName(), $components);

        $this->assertSame(['title', 'slug', 'canonical_source_url'], $fieldNames);

        // Verify each component is a TextInput
        foreach ($components as $component) {
            $this->assertInstanceOf(TextInput::class, $component);
        }
    }

    /**
     * Test field validation rules.
     */
    public function test_form_fields_validation_rules(): void
    {
        $schema = BuddhistVideoForm::configure(new Schema());
        $components = collect($schema->getComponents())->keyBy(fn ($c) => $c->getName());

        $this->assertTrue($components['title']->isRequired());
        $this->assertSame(255, $components['title']->getMaxLength());

        $this->assertTrue($components['slug']->isRequired());
        $this->assertSame(255, $components['slug']->getMaxLength());

        $this->assertTrue($components['canonical_source_url']->isRequired());
        $this->assertSame(1000, $components['canonical_source_url']->getMaxLength());
    }

    /**
     * Test YouTube and Vimeo URL validation and provider auto-detection.
     */
    public function test_provider_auto_detection_from_urls(): void
    {
        // Standard YouTube
        $yt = VideoProvider::detectFromUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertSame(VideoProvider::YouTube, $yt);
        $this->assertSame('dQw4w9WgXcQ', $yt->extractVideoId('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertTrue($yt->isValidVideoId('dQw4w9WgXcQ'));

        // Shortened youtu.be
        $short = VideoProvider::detectFromUrl('https://youtu.be/dQw4w9WgXcQ');
        $this->assertSame(VideoProvider::YouTube, $short);
        $this->assertSame('dQw4w9WgXcQ', $short->extractVideoId('https://youtu.be/dQw4w9WgXcQ'));

        // YouTube Shorts
        $shorts = VideoProvider::detectFromUrl('https://www.youtube.com/shorts/dQw4w9WgXcQ');
        $this->assertSame(VideoProvider::YouTube, $shorts);
        $this->assertSame('dQw4w9WgXcQ', $shorts->extractVideoId('https://www.youtube.com/shorts/dQw4w9WgXcQ'));

        // Vimeo
        $vm = VideoProvider::detectFromUrl('https://vimeo.com/76979871');
        $this->assertSame(VideoProvider::Vimeo, $vm);
        $this->assertSame('76979871', $vm->extractVideoId('https://vimeo.com/76979871'));
        $this->assertTrue($vm->isValidVideoId('76979871'));

        // Unsupported provider
        $unsupported = VideoProvider::detectFromUrl('https://www.dailymotion.com/video/x7tgad0');
        $this->assertNull($unsupported);

        // Invalid scheme / arbitrary URL
        $invalid = VideoProvider::detectFromUrl('javascript:alert(1)');
        $this->assertNull($invalid);
    }

    /**
     * Test creating a video with only 3 fields auto-populates technical metadata.
     */
    public function test_admin_can_create_draft_video_with_three_fields(): void
    {
        $video = BuddhistVideo::create([
            'title' => 'Pháp Thoại Về Tâm Tĩnh Lặng',
            'slug' => 'phap-thoai-ve-tam-tinh-lang',
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $this->assertNotNull($video->id);
        $this->assertNotNull($video->public_id);
        $this->assertSame('phap-thoai-ve-tam-tinh-lang', $video->slug);
        $this->assertSame(VideoProvider::YouTube, $video->provider);
        $this->assertSame('dQw4w9WgXcQ', $video->provider_video_id);
        $this->assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $video->thumbnail_url);
        $this->assertSame(VideoCategory::Other, $video->category);
        $this->assertSame(VideoEditorialStatus::Draft, $video->editorial_status);
        $this->assertNull($video->published_at);

        // Being a draft, it cannot be publicly embedded
        $this->assertNull($video->getEmbedUrl());
    }

    /**
     * Test publishing a video enables safe embed playback.
     */
    public function test_publishing_video_enables_safe_embed_playback(): void
    {
        $admin = User::factory()->admin()->create();

        $video = BuddhistVideo::create([
            'title' => 'Hướng Dẫn Tọa Thiền Chánh Niệm',
            'slug' => 'huong-dan-toa-thien-chanh-niem',
            'canonical_source_url' => 'https://vimeo.com/76979871',
        ]);

        $this->assertSame(VideoEditorialStatus::Draft, $video->editorial_status);
        $this->assertSame(VideoProvider::Vimeo, $video->provider);
        $this->assertSame('76979871', $video->provider_video_id);

        // Perform Publish Action (simulating table publish action)
        $video->editorial_status = VideoEditorialStatus::Published;
        $video->published_at = now();
        $video->reviewed_by = $admin->id;
        $video->reviewed_at = now();
        $video->embed_permission = VideoEmbedPermission::Approved;
        $video->copyright_status = VideoCopyrightStatus::VerifiedEmbeddable;
        $video->save();

        $this->assertSame('https://player.vimeo.com/video/76979871', $video->getEmbedUrl());
        $this->assertSame('approved_embed', $video->getPlaybackMode());
    }

    /**
     * Test editing title preserves existing slug if unchanged.
     */
    public function test_editing_title_preserves_existing_slug(): void
    {
        $video = BuddhistVideo::create([
            'title' => 'Tiêu Đề Ban Đầu',
            'slug' => 'tieu-de-ban-dau',
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $video->title = 'Tiêu Đề Đã Được Chỉnh Sửa';
        $video->save();

        $this->assertSame('tieu-de-ban-dau', $video->slug);
    }

    /**
     * Test unauthorized users cannot access or manage video resource.
     */
    public function test_authorization_checks(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->assertTrue(BuddhistVideoResource::canViewAny());
        $this->assertTrue(BuddhistVideoResource::canCreate());

        $video = BuddhistVideo::create([
            'title' => 'Video Thử Nghiệm',
            'slug' => 'video-thu-nghiem',
            'canonical_source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $this->assertTrue(BuddhistVideoResource::canEdit($video));
        $this->assertTrue(BuddhistVideoResource::canDelete($video));

        // Regular user
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->assertFalse(BuddhistVideoResource::canViewAny());
        $this->assertFalse(BuddhistVideoResource::canCreate());
        $this->assertFalse(BuddhistVideoResource::canEdit($video));
        $this->assertFalse(BuddhistVideoResource::canDelete($video));
    }
}
