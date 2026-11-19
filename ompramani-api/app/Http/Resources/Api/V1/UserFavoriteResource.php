<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\UserFavorite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserFavorite
 */
class UserFavoriteResource extends JsonResource
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
            'type' => $this->favoritable_type,
            'content_public_id' => $this->getContentPublicId(),
            'title' => $this->getContentTitle(),
            'thumbnail' => $this->getContentThumbnail(),
            'short_description' => $this->getContentShortDescription(),
            'content_url' => $this->getContentUrl(),
            'saved_at' => $this->created_at?->toIso8601String(),
            'is_available' => $this->isContentAvailable(),
        ];
    }
}
