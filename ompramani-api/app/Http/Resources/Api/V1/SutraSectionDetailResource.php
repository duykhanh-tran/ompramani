<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SutraSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SutraSection
 */
class SutraSectionDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sutra = $this->sutra ?? $this->version?->sutra;
        $version = $this->version ?? $sutra?->primaryVersion();

        return [
            'id' => $this->id,
            'sutra_id' => $this->sutra_id ?? $sutra?->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'parent_id' => $this->parent_id,
            'position' => $this->position,
            'source_page_start' => $this->source_page_start,
            'source_page_end' => $this->source_page_end,
            'version' => $version ? [
                'id' => $version->id,
                'name' => $version->name,
                'translator' => $version->translator,
                'language' => $version->language,
            ] : null,
            'sutra' => $sutra ? [
                'id' => $sutra->id,
                'title' => $sutra->title,
                'slug' => $sutra->slug,
            ] : null,
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
