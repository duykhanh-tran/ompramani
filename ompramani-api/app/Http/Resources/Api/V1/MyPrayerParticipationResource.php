<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\PrayerParticipation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PrayerParticipation
 */
class MyPrayerParticipationResource extends JsonResource
{
    /**
     * Transform the resource into an array for authenticated personal history.
     * Strictly excludes any other user's personal identifiers or non-public details.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $intention = $this->prayerIntention;

        return [
            'id' => $this->id,
            'participated_at' => $this->participated_at?->toIso8601String(),
            'prayer_intention' => $intention ? [
                'public_id' => $intention->public_id,
                'title' => $intention->title,
                'body' => $intention->body,
                'category' => $intention->category?->value,
                'category_label' => $intention->category?->label(),
                'published_at' => $intention->published_at?->toIso8601String(),
                'display_author_label' => 'Một Phật tử',
                'participation_count' => (int) ($intention->active_participations_count ?? $intention->activeParticipations()->count()),
            ] : null,
        ];
    }
}
