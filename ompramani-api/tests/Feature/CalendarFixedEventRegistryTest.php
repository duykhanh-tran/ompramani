<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CalendarMissingDayPolicy;
use App\Filament\Resources\CalendarEvents\CalendarEventResource;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\CalendarEventOccurrenceService;
use App\Services\Calendar\CalendarService;
use App\Services\Calendar\FixedCalendarEventRegistry;
use App\Services\Calendar\VietnameseLunisolarCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CalendarFixedEventRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected CalendarEventOccurrenceService $occurrenceService;

    protected CalendarService $calendarService;

    protected VietnameseLunisolarCalendar $calendarEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->occurrenceService = app(CalendarEventOccurrenceService::class);
        $this->calendarService = app(CalendarService::class);
        $this->calendarEngine = app(VietnameseLunisolarCalendar::class);
        FixedCalendarEventRegistry::syncToDatabase();
    }

    // ==============================================================
    // 1. GREGORIAN SOLAR EVENTS TESTS (Section 04 & 27)
    // ==============================================================

    public function test_all_gregorian_events_occur_on_exact_solar_dates(): void
    {
        $year = 2026;
        $occ = $this->occurrenceService->getOccurrencesBetween("{$year}-01-01", "{$year}-12-31");

        $expectedSolar = [
            'tet_duong_lich' => "{$year}-01-01",
            'quoc_te_phu_nu' => "{$year}-03-08",
            'giai_phong_mien_nam' => "{$year}-04-30",
            'quoc_te_lao_dong' => "{$year}-05-01",
            'quoc_te_thieu_nhi' => "{$year}-06-01",
            'quoc_khanh' => "{$year}-09-02",
        ];

        foreach ($expectedSolar as $key => $expectedDate) {
            $found = $occ->firstWhere('event_key', $key);
            $this->assertNotNull($found, "Event {$key} must exist in {$year}");
            $this->assertSame($expectedDate, $found['solar_date'], "Event {$key} must occur on {$expectedDate}");
        }
    }

    // ==============================================================
    // 2. TẾT NGUYÊN ĐÁN TESTS (Section 05 & 27)
    // ==============================================================

    public function test_tet_nguyen_dan_is_01_01_lunar_and_auto_converts_yearly(): void
    {
        // 2025: 01/01 Lunar is 2025-01-29
        $occ2025 = $this->occurrenceService->getOccurrencesBetween('2025-01-20', '2025-02-05');
        $tet2025 = $occ2025->firstWhere('event_key', 'tet_nguyen_dan');
        $this->assertNotNull($tet2025);
        $this->assertSame('2025-01-29', $tet2025['solar_date']);
        $this->assertSame(1, $tet2025['lunar_day']);
        $this->assertSame(1, $tet2025['lunar_month']);

        // 2026: 01/01 Lunar is 2026-02-17
        $occ2026 = $this->occurrenceService->getOccurrencesBetween('2026-02-10', '2026-02-25');
        $tet2026 = $occ2026->firstWhere('event_key', 'tet_nguyen_dan');
        $this->assertNotNull($tet2026);
        $this->assertSame('2026-02-17', $tet2026['solar_date']);

        // 2027: 01/01 Lunar is 2027-02-06
        $occ2027 = $this->occurrenceService->getOccurrencesBetween('2027-02-01', '2027-02-15');
        $tet2027 = $occ2027->firstWhere('event_key', 'tet_nguyen_dan');
        $this->assertNotNull($tet2027);
        $this->assertSame('2027-02-06', $tet2027['solar_date']);
    }

    // ==============================================================
    // 3. LẬP XUÂN SOLAR TERM TESTS (Section 07 & 27)
    // ==============================================================

    public function test_lap_xuan_is_derived_from_solar_terms_not_fixed_lunar_17_12(): void
    {
        // For multiple years, verify Lập Xuân is calculated by solar term and does NOT repeat fixed lunar 17/12
        $years = [2024, 2025, 2026, 2027, 2028];
        $lunarDates = [];

        foreach ($years as $y) {
            $solarDate = $this->occurrenceService->resolveLapXuanSolarDate($y);
            $this->assertNotNull($solarDate, "Lập xuân must be resolved for year {$y}");

            [$sy, $sm, $sd] = array_map('intval', explode('-', $solarDate));
            $lunar = $this->calendarEngine->convertSolarToLunar($sd, $sm, $sy, CalendarService::TIMEZONE_OFFSET);

            $lunarDates[$y] = "{$lunar['lunar_day']}/{$lunar['lunar_month']}";

            // Check that occurrence service returns it
            $occ = $this->occurrenceService->getOccurrencesBetween("{$y}-02-01", "{$y}-02-10");
            $lapXuan = $occ->firstWhere('event_key', 'ngay_lap_xuan');
            $this->assertNotNull($lapXuan, "Lập Xuân event must appear for {$y}");
            $this->assertSame($solarDate, $lapXuan['solar_date']);
        }

        // Prove lunar date is NOT constant across years (e.g. 17/12)
        $uniqueLunar = array_unique(array_values($lunarDates));
        $this->assertGreaterThan(1, count($uniqueLunar), 'Lập Xuân lunar date must vary across years: '.json_encode($lunarDates));
    }

    // ==============================================================
    // 4. MULTIPLE EVENTS ON SAME DAY (Section 11 & 27)
    // ==============================================================

    public function test_mung_1_lunar_has_multiple_events_without_date_deduplication(): void
    {
        // 2026-02-17 is Mùng 1 Tết Bính Ngọ (01/01 Lunar 2026)
        // Must contain:
        // 1. Tết Nguyên Đán
        // 2. Vía Phật Di Lặc
        // 3. Ngày Thập Trai
        $occ = $this->occurrenceService->getOccurrencesBetween('2026-02-17', '2026-02-17');

        $this->assertGreaterThanOrEqual(3, $occ->count());
        $keys = $occ->pluck('event_key')->all();

        $this->assertContains('tet_nguyen_dan', $keys);
        $this->assertContains('via_phat_di_lac', $keys);
        $this->assertContains('thap_trai', $keys);

        // Verify each has distinct occurrence_id
        $ids = $occ->pluck('occurrence_id')->all();
        $this->assertCount(count($ids), array_unique($ids));
    }

    // ==============================================================
    // 5. THẬP TRAI RULES (Section 08, 09 & 28)
    // ==============================================================

    public function test_thap_trai_full_month_has_exact_10_days(): void
    {
        // Lunar month 1 of 2026 has 30 days (full month): 2026-02-17 to 2026-03-18
        $monthLength = $this->calendarEngine->getLunarMonthLength(1, 2026, false, CalendarService::TIMEZONE_OFFSET);
        $this->assertSame(30, $monthLength);

        $occ = $this->occurrenceService->getOccurrencesBetween('2026-02-17', '2026-03-18');
        $thapTrai = $occ->where('event_key', 'thap_trai')->values();

        $this->assertCount(10, $thapTrai);
        $lunarDays = $thapTrai->pluck('lunar_day')->all();
        $this->assertSame([1, 8, 14, 15, 18, 23, 24, 28, 29, 30], $lunarDays);
    }

    public function test_thap_trai_short_month_has_exact_10_days_including_27_excluding_30(): void
    {
        // Lunar month 2 of 2026 has 29 days (short month): 2026-03-19 to 2026-04-16
        $monthLength = $this->calendarEngine->getLunarMonthLength(2, 2026, false, CalendarService::TIMEZONE_OFFSET);
        $this->assertSame(29, $monthLength);

        $occ = $this->occurrenceService->getOccurrencesBetween('2026-03-19', '2026-04-16');
        $thapTrai = $occ->where('event_key', 'thap_trai')->values();

        $this->assertCount(10, $thapTrai);
        $lunarDays = $thapTrai->pluck('lunar_day')->all();
        $this->assertSame([1, 8, 14, 15, 18, 23, 24, 27, 28, 29], $lunarDays);

        // Day 27 must be included, day 30 must NOT exist
        $this->assertContains(27, $lunarDays);
        $this->assertNotContains(30, $lunarDays);
    }

    public function test_thap_trai_applied_to_leap_months(): void
    {
        // Year 2025 has a leap month: Lunar Month 6 is repeated (month 6 regular & month 6 leap)
        $leapMonth = $this->calendarEngine->getYearLeapMonth(2025, CalendarService::TIMEZONE_OFFSET);
        $this->assertSame(6, $leapMonth);

        // In 2025, leap month 6 starts on solar 2025-07-25 and ends on 2025-08-22 (29 days)
        $occ = $this->occurrenceService->getOccurrencesBetween('2025-07-25', '2025-08-22');
        $leapThapTrai = $occ->where('event_key', 'thap_trai')->where('lunar_is_leap_month', true)->values();

        $this->assertCount(10, $leapThapTrai, 'Leap month must have exactly 10 Thập Trai days');
        foreach ($leapThapTrai as $item) {
            $this->assertTrue($item['lunar_is_leap_month']);
        }
    }

    // ==============================================================
    // 6. MONTH-END POLICY FOR DAY 30 LUNAR EVENTS (Section 10 & 29)
    // ==============================================================

    public function test_month_end_policy_for_day_30_lunar_events(): void
    {
        // 30/07 Âm: Vía Địa Tạng
        // 30/09 Âm: Vía Dược Sư
        // Check year 2026:
        // Lunar month 7 2026 length:
        $m7Len = $this->calendarEngine->getLunarMonthLength(7, 2026, false, CalendarService::TIMEZONE_OFFSET);
        // Lunar month 9 2026 length:
        $m9Len = $this->calendarEngine->getLunarMonthLength(9, 2026, false, CalendarService::TIMEZONE_OFFSET);

        // Query entire year 2026
        $occ = $this->occurrenceService->getOccurrencesBetween('2026-01-01', '2026-12-31');

        $diaTang = $occ->firstWhere('event_key', 'via_dia_tang_vuong_bo_tat');
        $this->assertNotNull($diaTang, 'Vía Địa Tạng must exist');

        if ($m7Len === 30) {
            $this->assertSame(30, $diaTang['lunar_day']);
            $this->assertFalse($diaTang['missing_day_adjusted']);
        } else {
            $this->assertSame(29, $diaTang['lunar_day']);
            $this->assertTrue($diaTang['missing_day_adjusted']);
            $this->assertSame(30, $diaTang['missing_day_original_day']);
        }

        $duocSu = $occ->firstWhere('event_key', 'via_duoc_su_thang_9');
        $this->assertNotNull($duocSu, 'Vía Dược Sư tháng 9 must exist');

        if ($m9Len === 30) {
            $this->assertSame(30, $duocSu['lunar_day']);
            $this->assertFalse($duocSu['missing_day_adjusted']);
        } else {
            $this->assertSame(29, $duocSu['lunar_day']);
            $this->assertTrue($duocSu['missing_day_adjusted']);
            $this->assertSame(30, $duocSu['missing_day_original_day']);
        }
    }

    public function test_annual_lunar_events_not_duplicated_in_leap_months(): void
    {
        // Year 2025 has leap month 6
        // Annual events like Quán Thế Âm Thành Đạo (19/06 Âm) or Chuyển Pháp Luân (15/06 Âm)
        // should occur ONCE in regular month 6, and NOT repeat in leap month 6
        $occ = $this->occurrenceService->getOccurrencesBetween('2025-06-01', '2025-08-30');

        $quanAmThanhDao = $occ->where('event_key', 'via_quan_the_am_thanh_dao')->values();
        $this->assertCount(1, $quanAmThanhDao);
        $this->assertFalse($quanAmThanhDao[0]['lunar_is_leap_month']);

        $chuyenPhapLuan = $occ->where('event_key', 'ngay_duc_phat_chuyen_phap_luan')->values();
        $this->assertCount(1, $chuyenPhapLuan);
        $this->assertFalse($chuyenPhapLuan[0]['lunar_is_leap_month']);
    }

    // ==============================================================
    // 7. CROSS YEAR BOUNDARY EVENTS (Section 12 & 29)
    // ==============================================================

    public function test_cross_year_boundary_events_displayed(): void
    {
        // 08/12 Lunar 2025 (Phật Thành Đạo) falls into January 2026 solar (2026-01-26)
        $occ = $this->occurrenceService->getOccurrencesBetween('2026-01-01', '2026-01-31');
        $thanhDao = $occ->firstWhere('event_key', 'via_phat_thich_ca_thanh_dao');

        $this->assertNotNull($thanhDao, 'Phật Thành Đạo from lunar year 2025 must appear in Jan 2026');
        $this->assertSame('2026-01-26', $thanhDao['solar_date']);
        $this->assertSame(8, $thanhDao['lunar_day']);
        $this->assertSame(12, $thanhDao['lunar_month']);
        $this->assertSame(2025, $thanhDao['lunar_year']);
    }

    // ==============================================================
    // 8. STABLE EVENT KEYS (Section 13 & 27)
    // ==============================================================

    public function test_stable_keys_for_all_48_fixed_events(): void
    {
        $registry = FixedCalendarEventRegistry::all();
        $this->assertCount(48, $registry);

        foreach ($registry as $key => $def) {
            $this->assertIsString($key);
            $this->assertSame($key, $def['key']);
            $this->assertNotEmpty($def['title']);
            $this->assertNotNull($def['event_type']);
            $this->assertNotNull($def['calendar_system']);
            $this->assertNotNull($def['recurrence_type']);
        }
    }

    // ==============================================================
    // 9. FILAMENT PERMISSIONS & DESCRIPTION EDITING (Section 16, 17 & 30)
    // ==============================================================

    public function test_admin_can_edit_description_with_unicode_and_it_persists(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $event = CalendarEvent::where('slug', 'dai_le_phat_dan')->first();
        $this->assertNotNull($event);

        $unicodeDescription = 'Đại lễ Phật Đản (Vesak) kỷ niệm ngày Đức Thế Tôn thị hiện đản sinh vì an lạc, vì hạnh phúc cho muôn loài.';
        $event->update([
            'description' => $unicodeDescription,
        ]);

        $this->assertSame($unicodeDescription, $event->fresh()->description);

        // Syncing registry should NOT overwrite the edited description
        FixedCalendarEventRegistry::syncToDatabase();
        $this->assertSame($unicodeDescription, $event->fresh()->description);

        // Visible via occurrence service
        $occ = $this->occurrenceService->getOccurrencesBetween('2026-05-01', '2026-06-05');
        $vesak = $occ->firstWhere('event_key', 'dai_le_phat_dan');
        $this->assertNotNull($vesak);
        $this->assertSame($unicodeDescription, $vesak['description']);
    }

    public function test_admin_cannot_create_or_delete_fixed_events_in_filament(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->assertFalse(CalendarEventResource::canCreate(), 'Admin must not create arbitrary events');

        $event = CalendarEvent::first();
        $this->assertNotNull($event);
        $this->assertFalse(CalendarEventResource::canDelete($event), 'Admin must not delete fixed events');
        $this->assertTrue(CalendarEventResource::canEdit($event), 'Admin can edit event description');
    }

    // ==============================================================
    // 10. DATABASE CLEANUP VERIFICATION (Section 19, 20 & 31)
    // ==============================================================

    public function test_calendar_moderation_and_adjustments_tables_removed(): void
    {
        $this->assertFalse(Schema::hasTable('calendar_event_exceptions'), 'calendar_event_exceptions table must be dropped');
        $this->assertFalse(Schema::hasTable('almanac_overrides'), 'almanac_overrides table must be dropped');

        $this->assertFalse(class_exists('App\Models\CalendarEventException'));
        $this->assertFalse(class_exists('App\Models\AlmanacOverride'));
        $this->assertFalse(class_exists('App\Filament\Resources\AlmanacOverrides\AlmanacOverrideResource'));
    }

    public function test_api_month_includes_is_thap_trai_flag(): void
    {
        $response = $this->getJson('/api/v1/calendar/month?year=2026&month=2');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'days' => [
                        '*' => ['solar_date', 'solar_day', 'lunar_day', 'lunar_month', 'is_thap_trai'],
                    ],
                ],
            ]);

        $days = collect($response->json('data.days'));
        // 2026-02-17 is 01/01 Lunar (Mùng 1 - Thập Trai)
        $mung1 = $days->firstWhere('solar_date', '2026-02-17');
        $this->assertNotNull($mung1);
        $this->assertTrue($mung1['is_thap_trai']);
    }
}
