<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Calendar\CalendarEventOccurrenceService;
use App\Services\Calendar\FixedCalendarEventRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CalendarEventOccurrenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CalendarEventOccurrenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CalendarEventOccurrenceService::class);
        FixedCalendarEventRegistry::syncToDatabase();
    }

    public function test_annual_solar_recurrence_for_fixed_events(): void
    {
        // Tết Dương Lịch (01/01 Solar)
        $occurrences = $this->service->getOccurrencesBetween('2025-01-01', '2026-01-01');

        $tetDuongLich = $occurrences->where('event_key', 'tet_duong_lich');
        $this->assertCount(2, $tetDuongLich);
        $this->assertSame(['2025-01-01', '2026-01-01'], $tetDuongLich->pluck('solar_date')->values()->all());
    }

    public function test_tet_nguyen_dan_calculated_dynamically_per_year(): void
    {
        // 2025: 01/01 Lunar is solar 2025-01-29
        $occ2025 = $this->service->getOccurrencesBetween('2025-01-20', '2025-02-05');
        $tet2025 = $occ2025->firstWhere('event_key', 'tet_nguyen_dan');
        $this->assertNotNull($tet2025);
        $this->assertSame('2025-01-29', $tet2025['solar_date']);

        // 2026: 01/01 Lunar is solar 2026-02-17
        $occ2026 = $this->service->getOccurrencesBetween('2026-02-10', '2026-02-25');
        $tet2026 = $occ2026->firstWhere('event_key', 'tet_nguyen_dan');
        $this->assertNotNull($tet2026);
        $this->assertSame('2026-02-17', $tet2026['solar_date']);
    }

    public function test_ngay_lap_xuan_uses_solar_terms(): void
    {
        // 2026 Lập Xuân is on 2026-02-05 (lunar 18/12)
        $lapXuanDate = $this->service->resolveLapXuanSolarDate(2026);
        $this->assertSame('2026-02-05', $lapXuanDate);

        $occ = $this->service->getOccurrencesBetween('2026-02-01', '2026-02-10');
        $lapXuan = $occ->firstWhere('event_key', 'ngay_lap_xuan');
        $this->assertNotNull($lapXuan);
        $this->assertSame('2026-02-05', $lapXuan['solar_date']);
    }

    public function test_multiple_events_on_same_day_are_not_deduplicated(): void
    {
        // On 2026-02-17 (01/01 Lunar 2026):
        // 1. Tết Nguyên Đán
        // 2. Vía Phật Di Lặc
        // 3. Ngày Thập Trai (Mùng 1)
        $occ = $this->service->getOccurrencesBetween('2026-02-17', '2026-02-17');

        $this->assertGreaterThanOrEqual(3, $occ->count());
        $keys = $occ->pluck('event_key')->all();
        $this->assertContains('tet_nguyen_dan', $keys);
        $this->assertContains('via_phat_di_lac', $keys);
        $this->assertContains('thap_trai', $keys);
    }

    public function test_thap_trai_rules_for_full_and_short_lunar_months(): void
    {
        // In 2026, month 1 lunar is full (30 days): 2026-02-17 to 2026-03-18
        // Month 2 lunar is short (29 days): 2026-03-19 to 2026-04-16
        $occMonth2 = $this->service->getOccurrencesBetween('2026-03-19', '2026-04-16');
        $thapTraiMonth2 = $occMonth2->where('event_key', 'thap_trai')->values();

        // Exactly 10 days for short month
        $this->assertCount(10, $thapTraiMonth2);

        $lunarDays = $thapTraiMonth2->pluck('lunar_day')->all();
        // Short month has day 27, does NOT have day 30
        $this->assertContains(27, $lunarDays);
        $this->assertNotContains(30, $lunarDays);
        $this->assertSame([1, 8, 14, 15, 18, 23, 24, 27, 28, 29], $lunarDays);
    }

    public function test_range_over_366_days_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->getOccurrencesBetween('2025-01-01', '2026-01-10');
    }
}
