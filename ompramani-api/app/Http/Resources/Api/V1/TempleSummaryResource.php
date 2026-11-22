<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Temple;
use App\Services\Media\MediaUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Temple
 */
class TempleSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'place_type' => $this->place_type?->value ?? $this->place_type,
            'place_type_label' => $this->place_type?->label() ?? $this->place_type,
            'short_description' => $this->short_description,
            'construction_year' => $this->construction_year,
            'opening_hours' => $this->opening_hours,
            'admission_fee' => $this->admission_fee,
            'google_maps_url' => $this->google_maps_url,
            'formatted_address' => $this->formattedAddress(),
            'province_name' => $this->province_name,
            'country_code' => $this->country_code,
            'cover_image' => MediaUrlResolver::resolve($this->getCoverImageUrl()),
            'traditions' => $this->traditions->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'is_primary' => (bool) ($t->pivot->is_primary ?? false),
            ]),
            'has_verified_coordinates' => $this->hasPublicCoordinates(),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
