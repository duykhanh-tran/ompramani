<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\BuddhistVideo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BuddhistVideo
 */
class VideoSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'slug' => $this->slug,
            'title' => $this->title,
            'short_description' => $this->short_description,
            'category' => $this->category?->value ?? $this->category,
            'category_label' => $this->category?->label() ?? $this->category,
            'provider' => $this->provider?->value ?? $this->provider,
            'provider_label' => $this->provider?->label() ?? $this->provider,
            'speaker_name' => $this->speaker_name,
            'duration_seconds' => $this->duration_seconds,
            'duration_formatted' => $this->formattedDuration(),
            'thumbnail_url' => $this->thumbnail_url,
            'playback_mode' => $this->getPlaybackMode(),
            'topics' => $this->whenLoaded('topics', function () {
                return $this->topics->map(fn ($topic) => [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'slug' => $topic->slug,
                ]);
            }),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
