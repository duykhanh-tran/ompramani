<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Calendar\VietnameseLunisolarCalendar;
use PHPUnit\Framework\TestCase;

class VietnameseLunisolarCalendarTest extends TestCase
{
    protected VietnameseLunisolarCalendar $calendar;

    /**
     * @var array<string, mixed>
     */
    protected array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = new VietnameseLunisolarCalendar;
        $fixturePath = dirname(__DIR__).'/Fixtures/calendar-reference.json';
        $this->fixtures = json_decode((string) file_get_contents($fixturePath), true);
    }

    public function test_independent_tet_fixtures_pass(): void
    {
        foreach ($this->fixtures['tet_fixtures'] as $fixture) {
            [$year, $month, $day] = array_map('intval', explode('-', $fixture['solar_date']));
            $lunar = $this->calendar->convertSolarToLunar($day, $month, $year);

            $this->assertSame($fixture['lunar_day'], $lunar['lunar_day'], "Solar date {$fixture['solar_date']} lunar day mismatch.");
            $this->assertSame($fixture['lunar_month'], $lunar['lunar_month'], "Solar date {$fixture['solar_date']} lunar month mismatch.");
            $this->assertSame($fixture['lunar_year'], $lunar['lunar_year'], "Solar date {$fixture['solar_date']} lunar year mismatch.");
            $this->assertSame($fixture['is_leap_month'], $lunar['is_leap_month'], "Solar date {$fixture['solar_date']} leap flag mismatch.");
            $this->assertSame($fixture['can_chi_year'], $lunar['year_can_chi'], "Solar date {$fixture['solar_date']} Can Chi year mismatch.");

            // Round trip
            $solar = $this->calendar->convertLunarToSolar(
                $lunar['lunar_day'],
                $lunar['lunar_month'],
                $lunar['lunar_year'],
                $lunar['is_leap_month']
            );
            $this->assertNotNull($solar);
            $this->assertSame($fixture['solar_date'], $solar['solar_date'], "Round trip failed for Tet date {$fixture['solar_date']}");
        }
    }

    public function test_independent_leap_month_fixtures_pass(): void
    {
        foreach ($this->fixtures['leap_month_fixtures'] as $fixture) {
            [$year, $month, $day] = array_map('intval', explode('-', $fixture['solar_date']));
            $lunar = $this->calendar->convertSolarToLunar($day, $month, $year);

            $this->assertSame($fixture['lunar_day'], $lunar['lunar_day'], "Leap fixture {$fixture['solar_date']} day mismatch ({$fixture['note']}).");
            $this->assertSame($fixture['lunar_month'], $lunar['lunar_month'], "Leap fixture {$fixture['solar_date']} month mismatch ({$fixture['note']}).");
            $this->assertSame($fixture['lunar_year'], $lunar['lunar_year'], "Leap fixture {$fixture['solar_date']} year mismatch ({$fixture['note']}).");
            $this->assertSame($fixture['is_leap_month'], $lunar['is_leap_month'], "Leap fixture {$fixture['solar_date']} leap flag mismatch ({$fixture['note']}).");

            // Round trip
            $solar = $this->calendar->convertLunarToSolar(
                $lunar['lunar_day'],
                $lunar['lunar_month'],
                $lunar['lunar_year'],
                $lunar['is_leap_month']
            );
            $this->assertNotNull($solar);
            $this->assertSame($fixture['solar_date'], $solar['solar_date'], "Round trip failed for leap fixture {$fixture['solar_date']}");
        }
    }

    public function test_independent_traditional_fixtures_pass(): void
    {
        foreach ($this->fixtures['traditional_fixtures'] as $fixture) {
            [$year, $month, $day] = array_map('intval', explode('-', $fixture['solar_date']));
            $lunar = $this->calendar->convertSolarToLunar($day, $month, $year);

            $this->assertSame($fixture['lunar_day'], $lunar['lunar_day'], "Traditional fixture {$fixture['solar_date']} day mismatch ({$fixture['note']}).");
            $this->assertSame($fixture['lunar_month'], $lunar['lunar_month'], "Traditional fixture {$fixture['solar_date']} month mismatch ({$fixture['note']}).");
            $this->assertSame($fixture['lunar_year'], $lunar['lunar_year'], "Traditional fixture {$fixture['solar_date']} year mismatch ({$fixture['note']}).");
            $this->assertSame($fixture['is_leap_month'], $lunar['is_leap_month'], "Traditional fixture {$fixture['solar_date']} leap flag mismatch ({$fixture['note']}).");

            // Round trip
            $solar = $this->calendar->convertLunarToSolar(
                $lunar['lunar_day'],
                $lunar['lunar_month'],
                $lunar['lunar_year'],
                $lunar['is_leap_month']
            );
            $this->assertNotNull($solar);
            $this->assertSame($fixture['solar_date'], $solar['solar_date'], "Round trip failed for traditional fixture {$fixture['solar_date']}");
        }
    }

    public function test_boundary_fixtures_pass(): void
    {
        foreach ($this->fixtures['boundary_fixtures'] as $fixture) {
            [$year, $month, $day] = array_map('intval', explode('-', $fixture['solar_date']));
            $lunar = $this->calendar->convertSolarToLunar($day, $month, $year);

            $this->assertSame($fixture['lunar_day'], $lunar['lunar_day'], "Boundary fixture {$fixture['solar_date']} day mismatch.");
            $this->assertSame($fixture['lunar_month'], $lunar['lunar_month'], "Boundary fixture {$fixture['solar_date']} month mismatch.");
            $this->assertSame($fixture['lunar_year'], $lunar['lunar_year'], "Boundary fixture {$fixture['solar_date']} year mismatch.");
            $this->assertSame($fixture['is_leap_month'], $lunar['is_leap_month'], "Boundary fixture {$fixture['solar_date']} leap flag mismatch.");
        }
    }

    public function test_historical_tet_1985_vietnam_differs_from_china(): void
    {
        // In 1985, Vietnam celebrated Tet on 1985-01-21 (UTC+7). China celebrated on 1985-02-20 (UTC+8).
        $lunarVn = $this->calendar->convertSolarToLunar(21, 1, 1985, 7.0);
        $this->assertSame(1, $lunarVn['lunar_day']);
        $this->assertSame(1, $lunarVn['lunar_month']);
        $this->assertSame(1985, $lunarVn['lunar_year']);
        $this->assertFalse($lunarVn['is_leap_month']);
        $this->assertSame('Ất Sửu', $lunarVn['year_can_chi']);

        $solar = $this->calendar->convertLunarToSolar(1, 1, 1985, false, 7.0);
        $this->assertNotNull($solar);
        $this->assertSame('1985-01-21', $solar['solar_date']);
    }

    public function test_leap_month_reversibility_and_distinction(): void
    {
        // 2023 has leap month 2.
        // Lunar 2023-02-10 (regular) vs Lunar 2023-02-10 (leap) must yield different solar dates.
        $solarRegular = $this->calendar->convertLunarToSolar(10, 2, 2023, false);
        $solarLeap = $this->calendar->convertLunarToSolar(10, 2, 2023, true);

        $this->assertNotNull($solarRegular);
        $this->assertNotNull($solarLeap);
        $this->assertNotSame($solarRegular['solar_date'], $solarLeap['solar_date']);
        $this->assertSame('2023-03-01', $solarRegular['solar_date']);
        $this->assertSame('2023-03-31', $solarLeap['solar_date']);

        // Reverse back: must preserve leap flag exactly
        $lunarRegular = $this->calendar->convertSolarToLunar($solarRegular['solar_day'], $solarRegular['solar_month'], $solarRegular['solar_year']);
        $this->assertSame(10, $lunarRegular['lunar_day']);
        $this->assertSame(2, $lunarRegular['lunar_month']);
        $this->assertSame(2023, $lunarRegular['lunar_year']);
        $this->assertFalse($lunarRegular['is_leap_month']);

        $lunarLeapResult = $this->calendar->convertSolarToLunar($solarLeap['solar_day'], $solarLeap['solar_month'], $solarLeap['solar_year']);
        $this->assertSame(10, $lunarLeapResult['lunar_day']);
        $this->assertSame(2, $lunarLeapResult['lunar_month']);
        $this->assertSame(2023, $lunarLeapResult['lunar_year']);
        $this->assertTrue($lunarLeapResult['is_leap_month']);
    }

    public function test_invalid_leap_month_returns_null(): void
    {
        // Year 2024 has NO leap month
        $this->assertNull($this->calendar->convertLunarToSolar(1, 2, 2024, true));

        // Year 2023 has leap month 2, but NOT month 5
        $this->assertNull($this->calendar->convertLunarToSolar(1, 5, 2023, true));
    }

    public function test_invalid_lunar_day_returns_null(): void
    {
        // Day 0 is invalid
        $this->assertNull($this->calendar->convertLunarToSolar(0, 1, 2024, false));

        // Day 31 is invalid (lunar months have at most 30 days)
        $this->assertNull($this->calendar->convertLunarToSolar(31, 1, 2024, false));

        // Month 2 leap in 2023 has 29 days, so day 30 is invalid
        $this->assertNull($this->calendar->convertLunarToSolar(30, 2, 2023, true));
    }

    public function test_lunar_month_length_calculation(): void
    {
        // Regular month 2 in 2023 has 30 days
        $this->assertSame(30, $this->calendar->getLunarMonthLength(2, 2023, false));

        // Leap month 2 in 2023 has 29 days
        $this->assertSame(29, $this->calendar->getLunarMonthLength(2, 2023, true));

        // Leap month 4 in 2020 has 29 days
        $this->assertSame(29, $this->calendar->getLunarMonthLength(4, 2020, true));
    }

    public function test_sexagenary_can_chi_calculations(): void
    {
        $this->assertSame('Quý Mão', $this->calendar->getYearCanChi(2023));
        $this->assertSame('Giáp Thìn', $this->calendar->getYearCanChi(2024));
        $this->assertSame('Ất Tỵ', $this->calendar->getYearCanChi(2025));
        $this->assertSame('Bính Ngọ', $this->calendar->getYearCanChi(2026));
        $this->assertSame('Ất Sửu', $this->calendar->getYearCanChi(1985));

        // Day Can Chi:
        // 2024-02-10 -> Giáp Thìn
        $jd2024 = $this->calendar->jdFromDate(10, 2, 2024);
        $this->assertSame('Giáp Thìn', $this->calendar->getDayCanChi($jd2024));

        // 2025-01-29 -> Mậu Tuất
        $jd2025 = $this->calendar->jdFromDate(29, 1, 2025);
        $this->assertSame('Mậu Tuất', $this->calendar->getDayCanChi($jd2025));
    }

    public function test_server_timezone_independence(): void
    {
        $originalTz = date_default_timezone_get();

        $timezones = ['UTC', 'Europe/London', 'America/New_York', 'Asia/Tokyo', 'Asia/Ho_Chi_Minh'];

        try {
            foreach ($timezones as $tz) {
                date_default_timezone_set($tz);

                $lunar = $this->calendar->convertSolarToLunar(17, 2, 2026);
                $this->assertSame(1, $lunar['lunar_day']);
                $this->assertSame(1, $lunar['lunar_month']);
                $this->assertSame(2026, $lunar['lunar_year']);
                $this->assertFalse($lunar['is_leap_month']);
                $this->assertSame('Bính Ngọ', $lunar['year_can_chi']);
            }
        } finally {
            date_default_timezone_set($originalTz);
        }
    }
}
