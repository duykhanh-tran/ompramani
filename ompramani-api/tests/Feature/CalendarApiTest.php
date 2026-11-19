<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalendarApiTest extends TestCase
{
    public function test_guest_can_convert_solar_to_lunar_with_date_query(): void
    {
        $response = $this->getJson('/api/v1/calendar/solar-to-lunar?date=2026-02-17');

        $response->assertStatus(200)
            ->assertJsonPath('data.solar_date', '2026-02-17')
            ->assertJsonPath('data.solar.year', 2026)
            ->assertJsonPath('data.solar.month', 2)
            ->assertJsonPath('data.solar.day', 17)
            ->assertJsonPath('data.solar.weekday_iso', 2)
            ->assertJsonPath('data.solar.weekday_name', 'Thứ Ba')
            ->assertJsonPath('data.lunar.year', 2026)
            ->assertJsonPath('data.lunar.month', 1)
            ->assertJsonPath('data.lunar.day', 1)
            ->assertJsonPath('data.lunar.is_leap_month', false)
            ->assertJsonPath('data.lunar.year_can_chi', 'Bính Ngọ')
            ->assertJsonPath('data.calendar.timezone', 'Asia/Ho_Chi_Minh');
    }

    public function test_guest_can_convert_solar_to_lunar_with_components(): void
    {
        $response = $this->getJson('/api/v1/calendar/solar-to-lunar?year=2024&month=2&day=10');

        $response->assertStatus(200)
            ->assertJsonPath('data.solar_date', '2024-02-10')
            ->assertJsonPath('data.lunar.year', 2024)
            ->assertJsonPath('data.lunar.month', 1)
            ->assertJsonPath('data.lunar.day', 1)
            ->assertJsonPath('data.lunar.year_can_chi', 'Giáp Thìn');
    }

    public function test_guest_can_convert_lunar_to_solar(): void
    {
        $response = $this->getJson('/api/v1/calendar/lunar-to-solar?year=2026&month=1&day=1&is_leap_month=false');

        $response->assertStatus(200)
            ->assertJsonPath('data.solar_date', '2026-02-17')
            ->assertJsonPath('data.solar.year', 2026)
            ->assertJsonPath('data.solar.month', 2)
            ->assertJsonPath('data.solar.day', 17)
            ->assertJsonPath('data.lunar.year', 2026)
            ->assertJsonPath('data.lunar.month', 1)
            ->assertJsonPath('data.lunar.day', 1)
            ->assertJsonPath('data.lunar.year_can_chi', 'Bính Ngọ');
    }

    public function test_lunar_to_solar_leap_month_handling(): void
    {
        // Regular month 2 day 10 in 2023
        $regularRes = $this->getJson('/api/v1/calendar/lunar-to-solar?year=2023&month=2&day=10&is_leap_month=false');
        $regularRes->assertStatus(200)
            ->assertJsonPath('data.solar_date', '2023-03-01')
            ->assertJsonPath('data.lunar.is_leap_month', false);

        // Leap month 2 day 10 in 2023
        $leapRes = $this->getJson('/api/v1/calendar/lunar-to-solar?year=2023&month=2&day=10&is_leap_month=true');
        $leapRes->assertStatus(200)
            ->assertJsonPath('data.solar_date', '2023-03-31')
            ->assertJsonPath('data.lunar.is_leap_month', true);
    }

    public function test_guest_can_get_day_calendar(): void
    {
        $response = $this->getJson('/api/v1/calendar/day?date=2025-01-29');

        $response->assertStatus(200)
            ->assertJsonPath('data.solar.date', '2025-01-29')
            ->assertJsonPath('data.lunar.year', 2025)
            ->assertJsonPath('data.lunar.month', 1)
            ->assertJsonPath('data.lunar.day', 1)
            ->assertJsonPath('data.lunar.year_can_chi', 'Ất Tỵ')
            ->assertJsonPath('data.calendar_timezone', 'Asia/Ho_Chi_Minh');
    }

    public function test_day_calendar_defaults_to_today(): void
    {
        $response = $this->getJson('/api/v1/calendar/day');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'solar' => ['date', 'year', 'month', 'day', 'weekday_iso', 'weekday_name'],
                    'lunar' => ['year', 'month', 'day', 'is_leap_month', 'month_length', 'year_can_chi', 'day_can_chi', 'solar_term'],
                    'calendar_timezone',
                ],
            ]);
    }

    public function test_guest_can_get_week_calendar(): void
    {
        $response = $this->getJson('/api/v1/calendar/week?date=2026-10-16');

        $response->assertStatus(200)
            ->assertJsonPath('data.selected_date', '2026-10-16')
            ->assertJsonPath('data.calendar_timezone', 'Asia/Ho_Chi_Minh')
            ->assertJsonCount(7, 'data.days')
            ->assertJsonPath('data.days.0.day_of_week', 'Thứ 2')
            ->assertJsonPath('data.days.0.solar_day', '12')
            ->assertJsonPath('data.days.4.day_of_week', 'Thứ 6')
            ->assertJsonPath('data.days.4.solar_day', '16')
            ->assertJsonPath('data.days.4.lunar_day', 7)
            ->assertJsonPath('data.days.4.lunar_month', 9)
            ->assertJsonPath('data.days.6.day_of_week', 'Chủ Nhật')
            ->assertJsonPath('data.days.6.solar_day', '18')
            ->assertJsonPath('data.days.6.is_sunday', true);
    }

    public function test_guest_can_get_month_calendar(): void
    {
        $response = $this->getJson('/api/v1/calendar/month?year=2026&month=2');

        $response->assertStatus(200)
            ->assertJsonPath('data.solar_year', 2026)
            ->assertJsonPath('data.solar_month', 2)
            ->assertJsonPath('data.days_in_month', 28)
            ->assertJsonPath('data.first_weekday_iso', 7) // 2026-02-01 is Sunday (7)
            ->assertJsonCount(28, 'data.days');

        // Check Feb 17 (day 17) -> Tet Binh Ngo (1/1 Lunar)
        $day17 = $response->json('data.days.16');
        $this->assertSame('2026-02-17', $day17['solar_date']);
        $this->assertSame(1, $day17['lunar_day']);
        $this->assertSame(1, $day17['lunar_month']);
        $this->assertSame(2026, $day17['lunar_year']);
        $this->assertTrue($day17['is_lunar_first_day']);
    }

    public function test_guest_can_get_calendar_config(): void
    {
        $response = $this->getJson('/api/v1/calendar/config');

        $response->assertStatus(200)
            ->assertJsonPath('data.system', 'vietnamese_lunisolar')
            ->assertJsonPath('data.calendar_timezone', 'Asia/Ho_Chi_Minh')
            ->assertJsonPath('data.min_supported_solar_date', '1900-01-31')
            ->assertJsonPath('data.max_supported_solar_date', '2100-12-31')
            ->assertJsonStructure([
                'data' => [
                    'system',
                    'calendar_timezone',
                    'timezone_offset',
                    'min_supported_solar_date',
                    'max_supported_solar_date',
                    'min_supported_lunar_year',
                    'max_supported_lunar_year',
                    'algorithm',
                    'capabilities',
                ],
            ]);
    }

    public function test_invalid_solar_date_returns_422(): void
    {
        // Impossible leap day (2025 is not a leap year)
        $this->getJson('/api/v1/calendar/solar-to-lunar?date=2025-02-29')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);

        // Impossible date
        $this->getJson('/api/v1/calendar/solar-to-lunar?date=2026-02-30')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);

        // Date outside supported range
        $this->getJson('/api/v1/calendar/solar-to-lunar?date=1899-12-31')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);

        // Missing date
        $this->getJson('/api/v1/calendar/solar-to-lunar')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_invalid_lunar_to_solar_returns_422(): void
    {
        // 2024 has NO leap month
        $this->getJson('/api/v1/calendar/lunar-to-solar?year=2024&month=2&day=1&is_leap_month=true')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_leap_month']);

        // Month 2 leap in 2023 has only 29 days, day 30 must fail
        $this->getJson('/api/v1/calendar/lunar-to-solar?year=2023&month=2&day=30&is_leap_month=true')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['day']);

        // Month 13 is invalid
        $this->getJson('/api/v1/calendar/lunar-to-solar?year=2023&month=13&day=1')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['month']);

        // Year outside supported range
        $this->getJson('/api/v1/calendar/lunar-to-solar?year=1899&month=1&day=1')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['year']);
    }

    public function test_invalid_month_query_returns_422(): void
    {
        $this->getJson('/api/v1/calendar/month?year=2026&month=13')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['month']);

        $this->getJson('/api/v1/calendar/month?year=1899&month=1')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['year']);
    }

    public function test_zero_database_queries_during_conversion(): void
    {
        DB::enableQueryLog();

        $this->getJson('/api/v1/calendar/solar-to-lunar?date=2026-02-17')->assertStatus(200);
        $this->getJson('/api/v1/calendar/lunar-to-solar?year=2026&month=1&day=1&is_leap_month=false')->assertStatus(200);
        $this->getJson('/api/v1/calendar/day?date=2026-02-17')->assertStatus(200);
        $this->getJson('/api/v1/calendar/month?year=2026&month=2')->assertStatus(200);

        $queries = DB::getQueryLog();
        $this->assertCount(0, $queries, 'Calendar endpoints must be purely algorithmic without database queries.');
    }
}
