<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Mantra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Mantra
 */
class MantraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $figureThumb = $this->figure?->thumbnail;
        $resolvedThumb = $figureThumb ?: $this->thumbnail;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'original_title' => $this->original_title,
            'short_description' => $this->short_description,
            'original_text' => $this->original_text,
            'vietnamese_transcription' => $this->vietnamese_transcription,
            'practice_guidance' => $this->practice_guidance,
            'audio_path' => $this->audio_path,
            'audio_url' => $this->audio_url,
            'thumbnail' => $resolvedThumb,
            'image_url' => $resolvedThumb,
            'tradition' => new TraditionResource($this->whenLoaded('tradition')),
            'figure' => new BuddhistFigureResource($this->whenLoaded('figure')),
            'buddhist_figure' => new BuddhistFigureResource($this->whenLoaded('figure')),
            'default_version' => new MantraVersionResource($this->whenLoaded('defaultVersion')),
            'versions' => MantraVersionResource::collection($this->whenLoaded('versions')),
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
