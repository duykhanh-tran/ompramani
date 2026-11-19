<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\CalendarEventException;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEventException>
 */
class CalendarEventExceptionFactory extends Factory
{
    protected $model = CalendarEventException::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'calendar_event_id' => CalendarEvent::factory(),
            'solar_year' => 2026,
            'action' => 'skip',
            'replacement_solar_date' => null,
            'reason' => 'Tổ chức trùng kỳ pháp hội quốc tế.',
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => null,
        ];
    }

    public function skip(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'solar_year' => $year,
            'action' => 'skip',
            'replacement_solar_date' => null,
        ]);
    }

    public function move(int $year, string $replacementDate): static
    {
        return $this->state(fn (array $attributes) => [
            'solar_year' => $year,
            'action' => 'move',
            'replacement_solar_date' => $replacementDate,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }
}
