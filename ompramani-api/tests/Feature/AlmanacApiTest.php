<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AlmanacMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlmanacApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AlmanacMethodSeeder::class);
    }

    public function test_guest_can_get_almanac_day_with_date_query(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/day?date=2026-02-17');

        $response->assertStatus(200)
            ->assertJsonPath('data.solar_date', '2026-02-17')
            ->assertJsonPath('data.calendar_timezone', 'Asia/Ho_Chi_Minh')
            ->assertJsonPath('data.lunar.year', 2026)
            ->assertJsonPath('data.lunar.month', 1)
            ->assertJsonPath('data.lunar.day', 1)
            ->assertJsonPath('data.can_chi.day', 'Nhâm Tuất')
            ->assertJsonPath('data.can_chi.year', 'Bính Ngọ')
            ->assertJsonPath('data.day_classification', 'hoang_dao')
            ->assertJsonPath('data.deity_name', 'Tư Mệnh')
            ->assertJsonPath('data.is_auspicious', true)
            ->assertJsonPath('data.has_override', false)
            ->assertJsonPath('data.verification_status', 'verified')
            ->assertJsonPath('data.method.key', 'thanh_long_deity')
            ->assertJsonCount(12, 'data.hours');
    }

    public function test_guest_can_get_almanac_day_default_today(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/day');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'solar_date',
                    'calendar_timezone',
                    'solar' => ['date', 'year', 'month', 'day', 'weekday_iso', 'weekday_name'],
                    'lunar' => ['year', 'month', 'day', 'is_leap_month', 'year_can_chi', 'day_can_chi'],
                    'can_chi' => ['year', 'month', 'day', 'method'],
                    'day_classification',
                    'deity_name',
                    'is_auspicious',
                    'has_override',
                    'hours',
                    'method' => ['key', 'name', 'version'],
                    'verification_status',
                    'disclaimer',
                ],
            ]);
    }

    public function test_guest_can_specify_method_query(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/day?date=2026-02-17&method=thanh_long_deity');

        $response->assertStatus(200)
            ->assertJsonPath('data.method.key', 'thanh_long_deity')
            ->assertJsonPath('data.day_classification', 'hoang_dao');
    }

    public function test_invalid_date_format_returns_422(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/day?date=2026-13-40');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);

        $response2 = $this->getJson('/api/v1/calendar/almanac/day?date=not-a-date');
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_unsupported_method_returns_422(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/day?date=2026-02-17&method=unknown_method_xyz');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['method']);
    }

    public function test_guest_can_get_almanac_month(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/month?year=2026&month=2');

        $response->assertStatus(200)
            ->assertJsonPath('data.solar_year', 2026)
            ->assertJsonPath('data.solar_month', 2)
            ->assertJsonPath('data.calendar_timezone', 'Asia/Ho_Chi_Minh')
            ->assertJsonCount(28, 'data.days')
            ->assertJsonPath('data.days.0.solar_date', '2026-02-01')
            ->assertJsonPath('data.days.0.auspicious_hours_count', 6)
            ->assertJsonPath('data.days.0.verification_status', 'verified');
    }

    public function test_invalid_month_query_returns_422(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/month?year=1850&month=13');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year', 'month']);
    }

    public function test_guest_can_get_almanac_methods(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'key',
                        'name',
                        'version',
                        'source_title',
                        'license',
                        'calendar_timezone',
                        'supported_start_date',
                        'supported_end_date',
                        'is_primary',
                    ],
                ],
            ]);

        $keys = collect($response->json('data'))->pluck('key');
        $this->assertContains('thanh_long_deity', $keys);
    }

    public function test_guest_can_get_almanac_config(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/config');

        $response->assertStatus(200)
            ->assertJsonPath('data.system', 'traditional_almanac')
            ->assertJsonPath('data.calendar_timezone', 'Asia/Ho_Chi_Minh')
            ->assertJsonCount(12, 'data.earthly_branches')
            ->assertJsonCount(10, 'data.heavenly_stems')
            ->assertJsonCount(12, 'data.yellow_black_deities')
            ->assertJsonPath('data.supported_range.start_date', '1900-01-31')
            ->assertJsonPath('data.supported_range.end_date', '2100-12-31');

        $this->assertStringContainsString('Không phải giáo lý Phật giáo', (string) $response->json('data.disclaimer'));
    }

    public function test_almanac_computes_cleanly_without_overrides(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/day?date=2026-02-17');

        $response->assertStatus(200)
            ->assertJsonPath('data.day_classification', 'hoang_dao')
            ->assertJsonPath('data.deity_name', 'Tư Mệnh')
            ->assertJsonPath('data.has_override', false);
    }

    public function test_zi_hour_start_date_anchoring_in_api_response(): void
    {
        $response = $this->getJson('/api/v1/calendar/almanac/day?date=2026-02-17');

        $response->assertStatus(200);

        $ziHour = $response->json('data.hours.0');
        $this->assertSame('ty', $ziHour['branch_key']);
        $this->assertSame('23:00', $ziHour['start_local_time']);
        $this->assertSame('01:00', $ziHour['end_local_time']);
        $this->assertTrue($ziHour['crosses_midnight']);
        $this->assertSame('2026-02-17 23:00:00', $ziHour['start_local_datetime']);
        $this->assertSame('2026-02-18 01:00:00', $ziHour['end_local_datetime']);
    }
}
