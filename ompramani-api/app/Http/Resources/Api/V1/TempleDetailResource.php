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
class TempleDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasCoords = $this->hasPublicCoordinates();

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'alternative_names' => $this->alternative_names ?? [],
            'place_type' => $this->place_type?->value ?? $this->place_type,
            'place_type_label' => $this->place_type?->label() ?? $this->place_type,
            'short_description' => $this->short_description,
            'description' => $this->description ?: implode("\n\n", array_filter([
                $this->highlights ? "ĐIỂM NỔI BẬT:\n{$this->highlights}" : null,
                $this->history ? "LỊCH SỬ HÌNH THÀNH:\n{$this->history}" : null,
                $this->architecture_culture ? "KIẾN TRÚC & VĂN HÓA:\n{$this->architecture_culture}" : null,
            ])),
            'construction_year' => $this->construction_year,
            'opening_hours' => $this->opening_hours,
            'admission_fee' => $this->admission_fee,
            'highlights' => $this->highlights,
            'history' => $this->history,
            'architecture_culture' => $this->architecture_culture,
            'google_maps_url' => $this->google_maps_url,
            'address' => [
                'formatted' => $this->formattedAddress(),
                'line' => $this->address_line,
                'ward' => $this->ward_name,
                'district' => $this->district_name,
                'province' => $this->province_name,
                'country_code' => $this->country_code,
                'postal_code' => $this->postal_code,
            ],
            'coordinates' => $hasCoords ? [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
                'accuracy' => $this->coordinate_accuracy?->value ?? $this->coordinate_accuracy,
                'accuracy_label' => $this->coordinate_accuracy?->label() ?? $this->coordinate_accuracy,
            ] : null,
            'public_access_status' => $this->public_access_status?->value ?? $this->public_access_status,
            'public_access_status_label' => $this->public_access_status?->label() ?? $this->public_access_status,
            'visit_information' => [
                'access_status' => $this->public_access_status?->value ?? $this->public_access_status,
                'access_status_label' => $this->public_access_status?->label() ?? $this->public_access_status,
                'notes' => $this->visit_notes,
                'verified_at' => $this->visit_notes_verified_at?->toIso8601String(),
                'source' => $this->visitNotesSource ? [
                    'title' => $this->visitNotesSource->source_title,
                    'url' => $this->visitNotesSource->source_url,
                ] : null,
            ],
            'contacts' => [
                'phone' => $this->public_phone,
                'email' => $this->public_email,
                'website' => $this->official_website,
            ],
            'traditions' => $this->traditions->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'is_primary' => (bool) ($t->pivot->is_primary ?? false),
            ]),
            'cover_image' => MediaUrlResolver::resolve($this->getCoverImageUrl()),
            'images' => $this->media->map(fn ($m) => [
                'id' => $m->id,
                'file_path' => MediaUrlResolver::resolve($m->file_path),
                'file_name' => $m->file_name,
                'alt_text' => $m->alt_text,
                'caption' => $m->caption,
                'credit' => $m->credit,
                'license_info' => $m->license_info,
                'sort_order' => $m->sort_order,
                'is_cover' => (bool) $m->is_cover,
            ]),
            'sources' => $this->sources->map(fn ($s) => [
                'title' => $s->source_title,
                'url' => $s->source_url,
                'type' => $s->source_type?->value ?? $s->source_type,
                'type_label' => $s->source_type?->label() ?? $s->source_type,
                'reference' => $s->reference_note,
                'checked_at' => $s->checked_at?->toIso8601String(),
            ]),
            'last_verified_at' => $this->last_verified_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
