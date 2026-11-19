<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BuddhistFigure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BuddhistFigure
 */
class BuddhistFigureResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'original_name' => $this->original_name,
            'invocation_text' => $this->invocation_text,
            'figure_type' => $this->figure_type,
            'short_description' => $this->short_description,
            'biography' => $this->biography,
            'vows_introduction' => $this->vows_introduction,
            'great_vows' => $this->great_vows,
            'vows' => BuddhistFigureVowResource::collection($this->whenLoaded('vows')),
            'iconography' => $this->iconography,
            'practice_guidance' => $this->practice_guidance,
            'thumbnail' => $this->thumbnail,
            'image_url' => $this->thumbnail,
            'tradition' => new TraditionResource($this->whenLoaded('tradition')),
            'traditions' => TraditionResource::collection($this->whenLoaded('traditions')),
            'sutras' => SutraResource::collection($this->whenLoaded('sutras')),
            'mantras' => MantraResource::collection($this->whenLoaded('mantras')),
            'audios' => RecitationAudioResource::collection($this->whenLoaded('publishedAudios')),
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
