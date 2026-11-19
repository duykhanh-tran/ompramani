<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MantraVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MantraVersion
 */
class MantraVersionResource extends JsonResource
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
            'mantra_id' => $this->mantra_id,
            'name' => $this->name,
            'language' => $this->language,
            'script' => $this->script,
            'content' => $this->content,
            'transliteration' => $this->transliteration,
            'translation' => $this->translation,
            'source' => $this->source,
            'notes' => $this->notes,
            'is_default' => (bool) $this->is_default,
            'mantra' => new MantraResource($this->whenLoaded('mantra')),
            'audios' => RecitationAudioResource::collection($this->whenLoaded('publishedAudios')),
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
