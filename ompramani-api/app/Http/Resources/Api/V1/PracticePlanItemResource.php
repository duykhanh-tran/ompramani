<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\PracticePlanItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PracticePlanItem
 */
class PracticePlanItemResource extends JsonResource
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
            'practice_plan_id' => $this->practice_plan_id,
            'practice_type' => $this->practice_type->value,
            'practice_label' => $this->practice_type->label(),
            'title' => $this->title,
            'schedule_type' => $this->schedule_type->value,
            'schedule_label' => $this->schedule_type->label(),
            'local_date' => $this->local_date?->format('Y-m-d'),
            'local_time' => $this->local_time,
            'weekdays' => $this->weekdays ?? [],
            'target_duration_seconds' => $this->target_duration_seconds,
            'target_repetitions' => $this->target_repetitions,
            'starts_on' => $this->starts_on?->format('Y-m-d'),
            'ends_on' => $this->ends_on?->format('Y-m-d'),
            'sort_order' => $this->sort_order,
            'is_enabled' => (bool) $this->is_enabled,
            'schedule_revision' => (int) $this->schedule_revision,
            'practice_reminder_id' => $this->practice_reminder_id,
            'has_reminder' => $this->practice_reminder_id !== null,
            'schedule_description' => $this->scheduleDescription(),
            'destination_url' => $this->destinationUrl(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
