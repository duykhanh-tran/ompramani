<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SutraVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SutraVersion
 */
class SutraVersionResource extends JsonResource
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
            'translator' => $this->translator,
            'language' => $this->language,
            'publisher' => $this->publisher,
            'publication_year' => $this->publication_year,
            'source' => $this->source,
            'notes' => $this->notes,
            'is_default' => (bool) $this->is_default,
            'status' => $this->status,
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
