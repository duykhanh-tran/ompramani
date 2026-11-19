<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CalendarEventRecurrence;
use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Enums\CalendarLeapPolicy;
use App\Enums\CalendarMissingDayPolicy;
use App\Enums\CalendarSystem;
use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => 'Lễ '.ucfirst($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'event_type' => CalendarEventType::BuddhistHoliday,
            'calendar_system' => CalendarSystem::Lunar,
            'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
            'lunar_month' => 4,
            'lunar_day' => 15,
            'lunar_days_json' => null,
            'lunar_leap_policy' => CalendarLeapPolicy::RegularOnly,
            'missing_day_policy' => CalendarMissingDayPolicy::LastDay,
            'specific_lunar_year' => null,
            'lunar_is_leap_month' => false,
            'solar_month' => null,
            'solar_day' => null,
            'solar_feb29_policy' => 'feb28',
            'specific_solar_date' => null,
            'is_general' => true,
            'source_title' => 'Kinh Phạm Võng',
            'source_url' => 'https://ompramani.test/sutras/pham-vong',
            'source_note' => 'Văn bản đối chiếu truyền thống Bắc truyền',
            'last_verified_at' => now(),
            'status' => CalendarEventStatus::Published,
            'reviewed_at' => now(),
            'reviewed_by' => null,
            'review_notes' => 'Đã thẩm định thông tin.',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CalendarEventStatus::Draft,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);
    }

    public function inReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CalendarEventStatus::InReview,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CalendarEventStatus::Published,
            'reviewed_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CalendarEventStatus::Archived,
        ]);
    }

    public function annualLunar(int $month = 4, int $day = 15, CalendarLeapPolicy $leapPolicy = CalendarLeapPolicy::RegularOnly): static
    {
        return $this->state(fn (array $attributes) => [
            'calendar_system' => CalendarSystem::Lunar,
            'recurrence_type' => CalendarEventRecurrence::AnnualLunar,
            'lunar_month' => $month,
            'lunar_day' => $day,
            'lunar_leap_policy' => $leapPolicy,
        ]);
    }

    public function monthlyLunar(array $days = [1, 15], CalendarMissingDayPolicy $missingDayPolicy = CalendarMissingDayPolicy::LastDay): static
    {
        return $this->state(fn (array $attributes) => [
            'calendar_system' => CalendarSystem::Lunar,
            'recurrence_type' => CalendarEventRecurrence::MonthlyLunar,
            'lunar_month' => null,
            'lunar_day' => null,
            'lunar_days_json' => $days,
            'missing_day_policy' => $missingDayPolicy,
        ]);
    }

    public function oneTimeLunar(int $year, int $month, int $day, bool $isLeapMonth = false): static
    {
        return $this->state(fn (array $attributes) => [
            'calendar_system' => CalendarSystem::Lunar,
            'recurrence_type' => CalendarEventRecurrence::OneTimeLunar,
            'specific_lunar_year' => $year,
            'lunar_month' => $month,
            'lunar_day' => $day,
            'lunar_is_leap_month' => $isLeapMonth,
        ]);
    }

    public function annualSolar(int $month = 1, int $day = 1, string $feb29Policy = 'feb28'): static
    {
        return $this->state(fn (array $attributes) => [
            'calendar_system' => CalendarSystem::Solar,
            'recurrence_type' => CalendarEventRecurrence::AnnualSolar,
            'solar_month' => $month,
            'solar_day' => $day,
            'solar_feb29_policy' => $feb29Policy,
            'lunar_month' => null,
            'lunar_day' => null,
        ]);
    }

    public function oneTimeSolar(string $solarDate): static
    {
        return $this->state(fn (array $attributes) => [
            'calendar_system' => CalendarSystem::Solar,
            'recurrence_type' => CalendarEventRecurrence::OneTimeSolar,
            'specific_solar_date' => $solarDate,
            'lunar_month' => null,
            'lunar_day' => null,
        ]);
    }
}
