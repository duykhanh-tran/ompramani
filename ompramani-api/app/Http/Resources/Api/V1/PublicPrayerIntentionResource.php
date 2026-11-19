<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\PrayerIntention;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PrayerIntention
 */
class PublicPrayerIntentionResource extends JsonResource
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
            'title' => $this->title,
            'body' => $this->body,
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'published_at' => $this->published_at?->toIso8601String(),
            'display_author_label' => 'Một Phật tử',
            'participation_count' => (int) ($this->active_participations_count ?? $this->activeParticipations()->count()),
            'approved_response_count' => (int) ($this->approved_responses_count ?? $this->approvedResponses()->count()),
        ];
    }
}
