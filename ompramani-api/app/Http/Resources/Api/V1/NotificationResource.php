<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $id
 * @property string $type
 * @property array $data
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 */
class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        return [
            'id' => $this->id,
            'type' => $data['type'] ?? 'prayer_community_notice',
            'category' => $data['category'] ?? 'moderation',
            'title' => $data['title'] ?? 'Thông báo từ OmPraMani',
            'message' => $data['message'] ?? '',
            'resource_type' => $data['resource_type'] ?? null,
            'resource_public_id' => $data['resource_public_id'] ?? null,
            'destination' => $data['destination'] ?? '/tai-khoan',
            'read_at' => $this->read_at?->toIso8601String(),
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
