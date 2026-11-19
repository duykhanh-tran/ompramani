<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\PracticePlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PracticePlan
 */
class PracticePlanResource extends JsonResource
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
            'description' => $this->description,
            'timezone' => $this->timezone,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'starts_on' => $this->starts_on?->format('Y-m-d'),
            'ends_on' => $this->ends_on?->format('Y-m-d'),
            'item_count' => $this->items()->count(),
            'items' => PracticePlanItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
