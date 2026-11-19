<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Sutra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sutra
 */
class SutraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'original_title' => $this->original_title,
            'translator' => $this->translator,
            'language' => $this->language ?? 'vi',
            'publisher' => $this->publisher,
            'publication_year' => $this->publication_year,
            'source' => $this->source,
            'notes' => $this->notes,
            'short_description' => $this->short_description,
            'thumbnail' => $this->thumbnail,
            'tradition' => new TraditionResource($this->whenLoaded('tradition')),
            'versions' => SutraVersionResource::collection($this->whenLoaded('versions')),
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
