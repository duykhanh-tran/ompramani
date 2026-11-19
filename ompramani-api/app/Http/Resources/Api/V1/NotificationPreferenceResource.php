<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property bool $moderation_updates_enabled
 * @property bool $new_response_updates_enabled
 * @property bool $participation_digest_enabled
 */
class NotificationPreferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'moderation_updates_enabled' => (bool) $this->moderation_updates_enabled,
            'new_response_updates_enabled' => (bool) $this->new_response_updates_enabled,
            'participation_digest_enabled' => (bool) $this->participation_digest_enabled,
        ];
    }
}
