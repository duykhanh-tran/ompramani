<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VideoCategory;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoEditorialStatus;
use App\Enums\VideoEmbedPermission;
use App\Enums\VideoProvider;
use App\Services\Search\SearchNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BuddhistVideo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'buddhist_videos';

    protected $fillable = [
        'public_id',
        'slug',
        'title',
        'short_description',
        'description',
        'category',
        'provider',
        'provider_video_id',
        'canonical_source_url',
        'source_title',
        'source_channel_name',
        'speaker_name',
        'duration_seconds',
        'language_code',
        'thumbnail_url',
        'copyright_status',
        'embed_permission',
        'editorial_status',
        'reviewed_by',
        'reviewed_at',
        'published_at',
        'normalized_title',
        'normalized_speaker',
    ];

    protected $attributes = [
        'category' => VideoCategory::Other->value,
        'provider' => VideoProvider::YouTube->value,
        'editorial_status' => VideoEditorialStatus::Draft->value,
        'copyright_status' => VideoCopyrightStatus::PendingReview->value,
        'embed_permission' => VideoEmbedPermission::ExternalOnly->value,
    ];

    protected $casts = [
        'category' => VideoCategory::class,
        'provider' => VideoProvider::class,
        'copyright_status' => VideoCopyrightStatus::class,
        'embed_permission' => VideoEmbedPermission::class,
        'editorial_status' => VideoEditorialStatus::class,
        'duration_seconds' => 'integer',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (BuddhistVideo $video): void {
            if (empty($video->public_id)) {
                $video->public_id = (string) Str::ulid();
            }

            if (empty($video->slug) && ! empty($video->title)) {
                $video->slug = static::generateUniqueSlug($video->title);
            }

            if (empty($video->category)) {
                $video->category = VideoCategory::Other;
            }

            if (empty($video->editorial_status)) {
                $video->editorial_status = VideoEditorialStatus::Draft;
            }

            // Auto-detect provider and extract video ID from canonical URL
            if (! empty($video->canonical_source_url)) {
                $detectedProvider = VideoProvider::detectFromUrl($video->canonical_source_url);
                if ($detectedProvider) {
                    $video->provider = $detectedProvider;
                    $videoId = $detectedProvider->extractVideoId($video->canonical_source_url);
                    if ($videoId && $detectedProvider->isValidVideoId($videoId)) {
                        $video->provider_video_id = $videoId;
                        if ($detectedProvider === VideoProvider::YouTube && empty($video->thumbnail_url)) {
                            $video->thumbnail_url = "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";
                        }
                    }
                }
            }

            if ($video->editorial_status === VideoEditorialStatus::Published && is_null($video->published_at)) {
                $video->published_at = now();
            }
        });

        static::updating(function (BuddhistVideo $video): void {
            if ($video->isDirty('canonical_source_url') && ! empty($video->canonical_source_url)) {
                $detectedProvider = VideoProvider::detectFromUrl($video->canonical_source_url);
                if ($detectedProvider) {
                    $video->provider = $detectedProvider;
                    $videoId = $detectedProvider->extractVideoId($video->canonical_source_url);
                    if ($videoId && $detectedProvider->isValidVideoId($videoId)) {
                        $video->provider_video_id = $videoId;
                        if ($detectedProvider === VideoProvider::YouTube) {
                            $video->thumbnail_url = "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";
                        }
                    }
                }
            }
        });

        static::saving(function (BuddhistVideo $video): void {
            $video->validateFields();
            $video->sanitizeInputs();
            $video->normalized_title = SearchNormalizer::normalize((string) $video->title);
            $video->normalized_speaker = filled($video->speaker_name) ? SearchNormalizer::normalize((string) $video->speaker_name) : null;
        });

        static::saved(function (BuddhistVideo $video): void {
            $video->clearDetailCache();
        });

        static::deleted(function (BuddhistVideo $video): void {
            $video->clearDetailCache();
        });
    }

    public function clearDetailCache(): void
    {
        Cache::forget("video_detail_{$this->slug}");
        Cache::forget("video_detail_{$this->public_id}");
        Cache::forget('video_categories_registry');
        Cache::forget('video_topics_list');
    }

    /**
     * Generate unique slug disambiguated by numeric sequence.
     */
    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if (empty($base)) {
            $base = 'video-phat-phap';
        }

        $candidate = $base;
        $counter = 1;

        while (static::where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->withTrashed()
            ->exists()) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }

    /**
     * Validate business constraints on video fields.
     */
    public function validateFields(): void
    {
        // 1. Validate Canonical Source URL
        if (empty($this->canonical_source_url)) {
            throw new InvalidArgumentException('Đường dẫn nguồn chính thức (canonical source URL) không được để trống.');
        }

        $url = (string) $this->canonical_source_url;
        if (! filter_var($url, FILTER_VALIDATE_URL) || (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://'))) {
            throw new InvalidArgumentException('Đường dẫn nguồn chính thức phải là một URL hợp lệ bắt đầu bằng http:// hoặc https://.');
        }

        // 2. Validate Duration
        if ($this->duration_seconds !== null && $this->duration_seconds < 0) {
            throw new InvalidArgumentException('Thời lượng video không thể là số âm.');
        }

        // 3. Validate Provider Video ID if not external
        $provider = $this->provider instanceof VideoProvider
            ? $this->provider
            : VideoProvider::tryFrom((string) $this->provider);

        if ($provider && $provider !== VideoProvider::External && filled($this->provider_video_id)) {
            if (! $provider->isValidVideoId((string) $this->provider_video_id)) {
                throw new InvalidArgumentException("Định danh video (ID) không hợp lệ đối với nền tảng {$provider->label()}.");
            }
        }
    }

    /**
     * Sanitize inputs to prevent XSS.
     */
    public function sanitizeInputs(): void
    {
        if (is_string($this->title)) {
            $this->title = strip_tags(trim($this->title));
        }
        if (is_string($this->short_description)) {
            $this->short_description = strip_tags(trim($this->short_description));
        }
        if (is_string($this->source_title)) {
            $this->source_title = strip_tags(trim($this->source_title));
        }
        if (is_string($this->source_channel_name)) {
            $this->source_channel_name = strip_tags(trim($this->source_channel_name));
        }
        if (is_string($this->speaker_name)) {
            $this->speaker_name = strip_tags(trim($this->speaker_name));
        }
    }

    /**
     * Build trusted embed URL if approved.
     */
    public function getEmbedUrl(): ?string
    {
        $provider = $this->provider instanceof VideoProvider
            ? $this->provider
            : VideoProvider::tryFrom((string) $this->provider);

        $perm = $this->embed_permission instanceof VideoEmbedPermission
            ? $this->embed_permission
            : VideoEmbedPermission::tryFrom((string) $this->embed_permission);

        $cright = $this->copyright_status instanceof VideoCopyrightStatus
            ? $this->copyright_status
            : VideoCopyrightStatus::tryFrom((string) $this->copyright_status);

        if ($provider
            && $provider !== VideoProvider::External
            && $perm === VideoEmbedPermission::Approved
            && in_array($cright, [VideoCopyrightStatus::VerifiedEmbeddable, VideoCopyrightStatus::LicensedHosted], true)
            && filled($this->provider_video_id)
        ) {
            return $provider->buildEmbedUrl($this->provider_video_id);
        }

        return null;
    }

    /**
     * Determine public playback mode.
     */
    public function getPlaybackMode(): string
    {
        if ($this->editorial_status === VideoEditorialStatus::Archived) {
            return 'unavailable';
        }

        if ($this->getEmbedUrl() !== null) {
            return 'approved_embed';
        }

        return 'external_link_only';
    }

    /**
     * Format duration seconds to human-readable string.
     */
    public function formattedDuration(): ?string
    {
        if ($this->duration_seconds === null || $this->duration_seconds <= 0) {
            return null;
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /* -------------------------------------------------------------------------
     * Relationships
     * ------------------------------------------------------------------------- */

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(VideoTopic::class, 'buddhist_video_topic')
            ->withTimestamps();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* -------------------------------------------------------------------------
     * Scopes
     * ------------------------------------------------------------------------- */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('editorial_status', VideoEditorialStatus::Published->value)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeByCategory(Builder $query, string|VideoCategory $category): Builder
    {
        $val = $category instanceof VideoCategory ? $category->value : $category;

        return $query->where('category', $val);
    }

    public function scopeByTopic(Builder $query, string|int $topic): Builder
    {
        return $query->whereHas('topics', function (Builder $q) use ($topic): void {
            if (is_numeric($topic)) {
                $q->where('video_topics.id', (int) $topic);
            } else {
                $q->where('video_topics.slug', $topic);
            }
        });
    }

    public function scopeBySpeaker(Builder $query, string $speaker): Builder
    {
        $speaker = trim($speaker);
        $normalized = SearchNormalizer::normalize($speaker);

        return $query->where(function (Builder $q) use ($speaker, $normalized): void {
            $q->where('speaker_name', 'LIKE', "%{$speaker}%")
                ->orWhere('normalized_speaker', 'LIKE', "%{$normalized}%");
        });
    }

    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        $keyword = trim($keyword);
        if (empty($keyword)) {
            return $query;
        }

        $normalized = SearchNormalizer::normalize($keyword);

        return $query->where(function (Builder $q) use ($keyword, $normalized): void {
            $q->where('normalized_title', 'LIKE', "%{$normalized}%")
                ->orWhere('normalized_speaker', 'LIKE', "%{$normalized}%")
                ->orWhere('title', 'LIKE', "%{$keyword}%")
                ->orWhere('speaker_name', 'LIKE', "%{$keyword}%")
                ->orWhere('source_channel_name', 'LIKE', "%{$keyword}%");
        });
    }
}
