<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property bool $practice_reminders_enabled
 * @property bool $quiet_hours_enabled
 * @property string|null $quiet_start_local_time
 * @property string|null $quiet_end_local_time
 * @property string|null $quiet_hours_timezone
 */
class PracticeReminderSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'practice_reminders_enabled' => (bool) $this->practice_reminders_enabled,
            'quiet_hours_enabled' => (bool) $this->quiet_hours_enabled,
            'quiet_start_local_time' => $this->quiet_start_local_time ?: '22:00',
            'quiet_end_local_time' => $this->quiet_end_local_time ?: '06:00',
            'quiet_hours_timezone' => $this->quiet_hours_timezone ?: 'Asia/Ho_Chi_Minh',
        ];
    }
}
