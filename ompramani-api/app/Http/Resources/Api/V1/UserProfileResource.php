<?php

namespace App\Http\Resources\Api\V1;

use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserProfile
 */
class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'dharma_name' => $this->dharma_name,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'preferred_tradition' => $this->preferredTradition ? [
                'id' => $this->preferredTradition->id,
                'name' => $this->preferredTradition->name,
                'slug' => $this->preferredTradition->slug,
            ] : null,
            'preferred_tradition_id' => $this->preferred_tradition_id,
            'location' => $this->location,
            'bio' => $this->bio,
            'dharma_journey_started_at' => $this->dharma_journey_started_at?->format('Y-m-d'),
            'timezone' => $this->timezone ?? config('app.timezone', 'Asia/Ho_Chi_Minh'),
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
