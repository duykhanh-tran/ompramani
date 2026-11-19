<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VideoEditorialStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserFavorite extends Model
{
    use HasFactory;

    /**
     * Allowed public content types for favorites.
     *
     * @var array<string, class-string<Model>>
     */
    public const ALLOWED_TYPES = [
        'sutra' => Sutra::class,
        'article' => Article::class,
        'video' => BuddhistVideo::class,
    ];

    protected $fillable = [
        'user_id',
        'favoritable_type',
        'favoritable_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('favoritable_type', $type);
    }

    /**
     * Check if the referenced content exists and is currently published.
     */
    public function isContentAvailable(): bool
    {
        $target = $this->favoritable;

        if (! $target) {
            return false;
        }

        return match ($this->favoritable_type) {
            'sutra' => $target->status === 'published'
                && ($target->published_at === null || $target->published_at->isPast()),
            'article' => method_exists($target, 'isPublished') ? $target->isPublished() : ($target->status === 'published'),
            'video' => ($target->editorial_status === VideoEditorialStatus::Published
                || $target->editorial_status?->value === VideoEditorialStatus::Published->value
                || $target->editorial_status === 'published')
                && ($target->published_at === null || $target->published_at->isPast()),
            default => false,
        };
    }

    /**
     * Get the public identifier (slug or public_id) for frontend routes.
     */
    public function getContentPublicId(): string
    {
        $target = $this->favoritable;

        if (! $target) {
            return (string) $this->favoritable_id;
        }

        return (string) ($target->slug ?? $target->public_id ?? $this->favoritable_id);
    }

    /**
     * Get safe title (hides private/draft title if unavailable).
     */
    public function getContentTitle(): string
    {
        if (! $this->isContentAvailable()) {
            return 'Nội dung hiện không khả dụng';
        }

        return (string) ($this->favoritable?->title ?? 'Nội dung không có tiêu đề');
    }

    /**
     * Get safe thumbnail URL.
     */
    public function getContentThumbnail(): ?string
    {
        if (! $this->isContentAvailable()) {
            return null;
        }

        $target = $this->favoritable;

        return match ($this->favoritable_type) {
            'sutra' => $target?->thumbnail,
            'article' => $target?->cover_image,
            'video' => $target?->thumbnail_url,
            default => null,
        };
    }

    /**
     * Get safe short description.
     */
    public function getContentShortDescription(): ?string
    {
        if (! $this->isContentAvailable()) {
            return null;
        }

        $target = $this->favoritable;

        return match ($this->favoritable_type) {
            'sutra' => $target?->short_description,
            'article' => $target?->excerpt,
            'video' => $target?->short_description ?: $target?->description,
            default => null,
        };
    }

    /**
     * Get standard canonical frontend route for content.
     */
    public function getContentUrl(): string
    {
        if (! $this->isContentAvailable()) {
            return '#';
        }

        $publicId = $this->getContentPublicId();

        return match ($this->favoritable_type) {
            'sutra' => "/kinh/{$publicId}",
            'article' => "/bai-viet/{$publicId}",
            'video' => "/video-phat-phap/{$publicId}",
            default => '#',
        };
    }
}
