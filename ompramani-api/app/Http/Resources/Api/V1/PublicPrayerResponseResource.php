<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\PrayerResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PrayerResponse
 */
class PublicPrayerResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array for public consumption.
     * STRICTLY excludes any personal identifiers, emails, names, internal IDs, and moderation notes.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'body' => $this->body,
            'published_at' => $this->published_at?->toIso8601String(),
            'display_author_label' => 'Một người đồng hành',
        ];
    }
}
