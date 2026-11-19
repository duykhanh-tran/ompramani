<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\PracticeReminderScheduleType;
use App\Enums\PracticeType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property PracticeType $practice_type
 * @property string|null $title
 * @property PracticeReminderScheduleType $schedule_type
 * @property string $local_time
 * @property string $timezone
 * @property Carbon|null $local_date
 * @property array|null $weekdays
 * @property bool $is_enabled
 * @property int $schedule_revision
 * @property Carbon|null $next_due_at_utc
 * @property Carbon|null $last_scheduled_at_utc
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PracticeReminderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $nextDueLocal = null;
        if ($this->next_due_at_utc && $this->timezone) {
            try {
                $nextDueLocal = Carbon::instance($this->next_due_at_utc)
                    ->setTimezone($this->timezone)
                    ->format('d/m/Y H:i');
            } catch (\Throwable) {
                $nextDueLocal = null;
            }
        }

        return [
            'id' => $this->id,
            'practice_type' => $this->practice_type->value,
            'practice_label' => $this->practice_type->label(),
            'title' => $this->title,
            'schedule_type' => $this->schedule_type->value,
            'schedule_label' => $this->schedule_type->label(),
            'local_time' => $this->local_time,
            'timezone' => $this->timezone,
            'local_date' => $this->local_date?->format('Y-m-d'),
            'weekdays' => $this->weekdays ?? [],
            'is_enabled' => (bool) $this->is_enabled,
            'schedule_revision' => (int) $this->schedule_revision,
            'next_due_at' => $this->next_due_at_utc?->toIso8601String(),
            'next_due_local' => $nextDueLocal,
            'schedule_description' => $this->scheduleDescription(),
            'destination_url' => $this->destinationUrl(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
