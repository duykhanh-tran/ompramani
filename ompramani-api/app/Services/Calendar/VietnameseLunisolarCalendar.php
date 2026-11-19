<?php

declare(strict_types=1);

namespace App\Services\Calendar;

/**
 * Vietnamese Lunisolar Calendar Engine.
 *
 * Implements the astronomical algorithms for the Vietnamese Lunisolar Calendar
 * adapted from Jean Meeus (Astronomical Algorithms, 1998) and Dr. Hồ Ngọc Đức's
 * validated formulation for the UTC+07:00 meridian (Asia/Ho_Chi_Minh).
 */
class VietnameseLunisolarCalendar
{
    public const PI = M_PI;

    public const CAN = [
        'Giáp', 'Ất', 'Bính', 'Đinh', 'Mậu',
        'Kỷ', 'Canh', 'Tân', 'Nhâm', 'Quý',
    ];

    public const CHI = [
        'Tý', 'Sửu', 'Dần', 'Mão', 'Thìn', 'Tỵ',
        'Ngọ', 'Mùi', 'Thân', 'Dậu', 'Tuất', 'Hợi',
    ];

    public const SOLAR_TERMS = [
        'Xuân phân', 'Thanh minh', 'Cốc vũ', 'Lập hạ',
        'Tiểu mãn', 'Mang chủng', 'Hạ chí', 'Tiểu thử',
        'Đại thử', 'Lập thu', 'Xử thử', 'Bạch lộ',
        'Thu phân', 'Hàn lộ', 'Sương giáng', 'Lập đông',
        'Tiểu tuyết', 'Đại tuyết', 'Đông chí', 'Tiểu hàn',
        'Đại hàn', 'Lập xuân', 'Vũ thủy', 'Kinh trập',
    ];

    public const WEEKDAYS = [
        1 => 'Thứ Hai',
        2 => 'Thứ Ba',
        3 => 'Thứ Tư',
        4 => 'Thứ Năm',
        5 => 'Thứ Sáu',
        6 => 'Thứ Bảy',
        7 => 'Chủ Nhật',
    ];

    /**
     * Compute Julian Day Number from Gregorian civil date (dd, mm, yy).
     */
    public function jdFromDate(int $dd, int $mm, int $yy): int
    {
        $a = intdiv(14 - $mm, 12);
        $y = $yy + 4800 - $a;
        $m = $mm + 12 * $a - 3;
        $jd = $dd + intdiv(153 * $m + 2, 5) + 365 * $y + intdiv($y, 4) - intdiv($y, 100) + intdiv($y, 400) - 32045;
        if ($jd < 2299161) {
            $jd = $dd + intdiv(153 * $m + 2, 5) + 365 * $y + intdiv($y, 4) - 32083;
        }

        return $jd;
    }

    /**
     * Convert Julian Day Number to Gregorian civil date [$day, $month, $year].
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public function jdToDate(int $jd): array
    {
        if ($jd > 2299160) {
            $a = $jd + 32044;
            $b = intdiv(4 * $a + 3, 146097);
            $c = $a - intdiv(146097 * $b, 4);
        } else {
            $b = 0;
            $c = $jd + 32082;
        }
        $d = intdiv(4 * $c + 3, 1461);
        $e = $c - intdiv(1461 * $d, 4);
        $m = intdiv(5 * $e + 2, 153);
        $day = $e - intdiv(153 * $m + 2, 5) + 1;
        $month = $m + 3 - 12 * intdiv($m, 10);
        $year = $b * 100 + $d - 4800 + intdiv($m, 10);

        return [$day, $month, $year];
    }

    /**
     * Solar longitude according to Jean Meeus AA98.
     */
    public function sunLongitudeAA98(float $jdn): float
    {
        $julianTime = ($jdn - 2451545.0) / 36525.0;
        $squareJulianTime = $julianTime * $julianTime;
        $dr = self::PI / 180.0;
        $meanAnomaly = 357.52910 + 35999.05030 * $julianTime - 0.0001559 * $squareJulianTime - 0.00000048 * $julianTime * $squareJulianTime;
        $meanLongitude = 280.46645 + 36000.76983 * $julianTime + 0.0003032 * $squareJulianTime;
        $dl = (1.914600 - 0.004817 * $julianTime - 0.000014 * $squareJulianTime) * sin($dr * $meanAnomaly);
        $dl += (0.019993 - 0.000101 * $julianTime) * sin($dr * 2 * $meanAnomaly) + 0.000290 * sin($dr * 3 * $meanAnomaly);
        $trueLongitude = $meanLongitude + $dl;
        $trueLongitude -= 360.0 * floor($trueLongitude / 360.0);

        return $trueLongitude;
    }

    /**
     * Sun longitude at midnight in the specified timezone.
     */
    public function getSunLongitude(int $dayNumber, float $timeZone = 7.0): float
    {
        return $this->sunLongitudeAA98($dayNumber - 0.5 - $timeZone / 24.0);
    }

    /**
     * Day of the k-th new moon (astronomical calculation).
     */
    public function getNewMoonDay(int $k, float $timeZone = 7.0): int
    {
        $dr = self::PI / 180.0;
        $julianTime = $k / 1236.85;
        $squareJulianTime = $julianTime * $julianTime;
        $cubicJulianTime = $squareJulianTime * $julianTime;

        $jd1 = 2415020.75933 + 29.53058868 * $k + 0.0001178 * $squareJulianTime - 0.000000155 * $cubicJulianTime;
        $jd1 += 0.00033 * sin((166.56 + 132.87 * $julianTime - 0.009173 * $squareJulianTime) * $dr);
        $sunMeanAnomaly = 359.2242 + 29.10535608 * $k - 0.0000333 * $squareJulianTime - 0.00000347 * $cubicJulianTime;
        $moonMeanAnomaly = 306.0253 + 385.81691806 * $k + 0.0107306 * $squareJulianTime + 0.00001236 * $cubicJulianTime;
        $f = 21.2964 + 390.67050646 * $k - 0.0016528 * $squareJulianTime - 0.00000239 * $cubicJulianTime;

        $c1 = (0.1734 - 0.000393 * $julianTime) * sin($sunMeanAnomaly * $dr) + 0.0021 * sin(2 * $dr * $sunMeanAnomaly);
        $c1 = $c1 - 0.4068 * sin($moonMeanAnomaly * $dr) + 0.0161 * sin($dr * 2 * $moonMeanAnomaly);
        $c1 -= 0.0004 * sin($dr * 3 * $moonMeanAnomaly);
        $c1 = $c1 + 0.0104 * sin($dr * 2 * $f) - 0.0051 * sin($dr * ($sunMeanAnomaly + $moonMeanAnomaly));
        $c1 = $c1 - 0.0074 * sin($dr * ($sunMeanAnomaly - $moonMeanAnomaly)) + 0.0004 * sin($dr * (2 * $f + $sunMeanAnomaly));
        $c1 = $c1 - 0.0004 * sin($dr * (2 * $f - $sunMeanAnomaly)) - 0.0006 * sin($dr * (2 * $f + $moonMeanAnomaly));
        $c1 += 0.0010 * sin($dr * (2 * $f - $moonMeanAnomaly)) + 0.0005 * sin($dr * (2 * $moonMeanAnomaly + $sunMeanAnomaly));

        if ($julianTime < -11) {
            $deltAt = 0.001 + 0.000839 * $julianTime + 0.0002261 * $squareJulianTime - 0.00000845 * $cubicJulianTime - 0.000000081 * $julianTime * $cubicJulianTime;
        } else {
            $deltAt = -0.000278 + 0.000265 * $julianTime + 0.000262 * $squareJulianTime;
        }
        $jd = $jd1 + $c1 - $deltAt;

        return (int) floor($jd + 0.5 + $timeZone / 24.0);
    }

    /**
     * Compute the day of the 11th lunar month of the given Gregorian solar year.
     */
    public function getLunarMonth11(int $yy, float $timeZone = 7.0): int
    {
        $off = $this->jdFromDate(31, 12, $yy) - 2415021.076998695;
        $k = (int) floor($off / 29.530588853);
        $nm = $this->getNewMoonDay($k, $timeZone);
        $sunLong = (int) floor($this->getSunLongitude($nm, $timeZone) / 30.0);
        if ($sunLong >= 9) {
            $nm = $this->getNewMoonDay($k - 1, $timeZone);
        }

        return $nm;
    }

    /**
     * Find the offset of the leap month in the lunar year starting from month 11 ($a11).
     */
    public function getLeapMonthOffset(int $a11, float $timeZone = 7.0): int
    {
        $k = (int) floor(0.5 + ($a11 - 2415021.076998695) / 29.530588853);
        $i = 1;
        $arc = (int) floor($this->getSunLongitude($this->getNewMoonDay($k + $i, $timeZone), $timeZone) / 30.0);
        do {
            $last = $arc;
            $i++;
            $arc = (int) floor($this->getSunLongitude($this->getNewMoonDay($k + $i, $timeZone), $timeZone) / 30.0);
        } while ($arc != $last && $i < 14);

        return $i - 1;
    }

    /**
     * Convert solar date (dd, mm, yy) to Vietnamese lunar date.
     *
     * @return array{
     *     lunar_day: int,
     *     lunar_month: int,
     *     lunar_year: int,
     *     is_leap_month: bool,
     *     lunar_month_length: int,
     *     is_lunar_leap_year: bool,
     *     leap_month_number: ?int,
     *     year_can_chi: string,
     *     day_can_chi: string,
     *     month_can_chi: string,
     *     solar_term: string
     * }
     */
    public function convertSolarToLunar(int $dd, int $mm, int $yy, float $timeZone = 7.0): array
    {
        $dayNumber = $this->jdFromDate($dd, $mm, $yy);
        $k = (int) floor(($dayNumber - 2415021.076998695) / 29.530588853);
        $monthStart = $this->getNewMoonDay($k + 1, $timeZone);
        if ($monthStart > $dayNumber) {
            $monthStart = $this->getNewMoonDay($k, $timeZone);
        }

        $a11 = $this->getLunarMonth11($yy, $timeZone);
        $b11 = $a11;
        if ($a11 >= $monthStart) {
            $lunarYear = $yy;
            $a11 = $this->getLunarMonth11($yy - 1, $timeZone);
        } else {
            $lunarYear = $yy + 1;
            $b11 = $this->getLunarMonth11($yy + 1, $timeZone);
        }

        $lunarDay = $dayNumber - $monthStart + 1;
        $diff = (int) floor(($monthStart - $a11) / 29.0);
        $lunarLeap = false;
        $lunarMonth = $diff + 11;
        $hasLeap = ($b11 - $a11 > 365);
        $leapMonthNumber = null;

        if ($hasLeap) {
            $leapMonthDiff = $this->getLeapMonthOffset($a11, $timeZone);
            $leapMonthNumber = $leapMonthDiff - 2;
            if ($leapMonthNumber < 0) {
                $leapMonthNumber += 12;
            }
            if ($diff >= $leapMonthDiff) {
                $lunarMonth = $diff + 10;
                if ($diff === $leapMonthDiff) {
                    $lunarLeap = true;
                }
            }
        }

        if ($lunarMonth > 12) {
            $lunarMonth -= 12;
        }
        if ($lunarMonth >= 11 && $diff < 4) {
            $lunarYear -= 1;
        }

        // Exact month length: next new moon - current new moon
        $kCurrent = (int) floor(0.5 + ($monthStart - 2415021.076998695) / 29.530588853);
        $nextMonthStart = $this->getNewMoonDay($kCurrent + 1, $timeZone);
        $monthLength = $nextMonthStart - $monthStart;

        // Can Chi calculations
        $yearCanChi = $this->getYearCanChi($lunarYear);
        $dayCanChi = $this->getDayCanChi($dayNumber);
        $monthCanChi = $this->getMonthCanChi($lunarMonth, $lunarYear);
        $solarTerm = $this->getSolarTerm($dayNumber, $timeZone);

        return [
            'lunar_day' => $lunarDay,
            'lunar_month' => $lunarMonth,
            'lunar_year' => $lunarYear,
            'is_leap_month' => $lunarLeap,
            'lunar_month_length' => $monthLength,
            'is_lunar_leap_year' => $hasLeap,
            'leap_month_number' => $hasLeap ? $leapMonthNumber : null,
            'year_can_chi' => $yearCanChi,
            'day_can_chi' => $dayCanChi,
            'month_can_chi' => $monthCanChi,
            'solar_term' => $solarTerm,
        ];
    }

    /**
     * Convert Vietnamese lunar date to Gregorian solar date.
     * Returns null if the lunar date or leap month flag is invalid.
     *
     * @return array{solar_day: int, solar_month: int, solar_year: int, solar_date: string}|null
     */
    public function convertLunarToSolar(int $lunarDay, int $lunarMonth, int $lunarYear, bool $isLeapMonth, float $timeZone = 7.0): ?array
    {
        if ($lunarMonth < 1 || $lunarMonth > 12) {
            return null;
        }

        if ($lunarMonth < 11) {
            $a11 = $this->getLunarMonth11($lunarYear - 1, $timeZone);
            $b11 = $this->getLunarMonth11($lunarYear, $timeZone);
        } else {
            $a11 = $this->getLunarMonth11($lunarYear, $timeZone);
            $b11 = $this->getLunarMonth11($lunarYear + 1, $timeZone);
        }

        $k = (int) floor(0.5 + ($a11 - 2415021.076998695) / 29.530588853);
        $off = $lunarMonth - 11;
        if ($off < 0) {
            $off += 12;
        }

        $hasLeap = ($b11 - $a11 > 365);
        if ($hasLeap) {
            $leapOff = $this->getLeapMonthOffset($a11, $timeZone);
            $leapMonth = $leapOff - 2;
            if ($leapMonth < 0) {
                $leapMonth += 12;
            }

            if ($isLeapMonth && $lunarMonth !== $leapMonth) {
                return null; // This year does not have this leap month
            }

            if ($isLeapMonth || $off >= $leapOff) {
                $off += 1;
            }
        } elseif ($isLeapMonth) {
            return null; // Year has no leap month
        }

        $monthStart = $this->getNewMoonDay($k + $off, $timeZone);
        $nextMonthStart = $this->getNewMoonDay($k + $off + 1, $timeZone);
        $monthLength = $nextMonthStart - $monthStart;

        if ($lunarDay < 1 || $lunarDay > $monthLength) {
            return null; // Day out of range for this lunar month
        }

        $jd = $monthStart + $lunarDay - 1;
        [$solarDay, $solarMonth, $solarYear] = $this->jdToDate($jd);

        return [
            'solar_day' => $solarDay,
            'solar_month' => $solarMonth,
            'solar_year' => $solarYear,
            'solar_date' => sprintf('%04d-%02d-%02d', $solarYear, $solarMonth, $solarDay),
        ];
    }

    /**
     * Get the length of a specific lunar month (29 or 30 days).
     */
    public function getLunarMonthLength(int $lunarMonth, int $lunarYear, bool $isLeapMonth, float $timeZone = 7.0): ?int
    {
        if ($lunarMonth < 1 || $lunarMonth > 12) {
            return null;
        }

        if ($lunarMonth < 11) {
            $a11 = $this->getLunarMonth11($lunarYear - 1, $timeZone);
            $b11 = $this->getLunarMonth11($lunarYear, $timeZone);
        } else {
            $a11 = $this->getLunarMonth11($lunarYear, $timeZone);
            $b11 = $this->getLunarMonth11($lunarYear + 1, $timeZone);
        }

        $k = (int) floor(0.5 + ($a11 - 2415021.076998695) / 29.530588853);
        $off = $lunarMonth - 11;
        if ($off < 0) {
            $off += 12;
        }

        $hasLeap = ($b11 - $a11 > 365);
        if ($hasLeap) {
            $leapOff = $this->getLeapMonthOffset($a11, $timeZone);
            $leapMonth = $leapOff - 2;
            if ($leapMonth < 0) {
                $leapMonth += 12;
            }

            if ($isLeapMonth && $lunarMonth !== $leapMonth) {
                return null;
            }

            if ($isLeapMonth || $off >= $leapOff) {
                $off += 1;
            }
        } elseif ($isLeapMonth) {
            return null;
        }

        $monthStart = $this->getNewMoonDay($k + $off, $timeZone);
        $nextMonthStart = $this->getNewMoonDay($k + $off + 1, $timeZone);

        return $nextMonthStart - $monthStart;
    }

    /**
     * Check if a lunar year has a leap month and return its month number (1..12), or null if none.
     */
    public function getYearLeapMonth(int $lunarYear, float $timeZone = 7.0): ?int
    {
        $a11 = $this->getLunarMonth11($lunarYear - 1, $timeZone);
        $b11 = $this->getLunarMonth11($lunarYear, $timeZone);

        if ($b11 - $a11 > 365) {
            $leapOff = $this->getLeapMonthOffset($a11, $timeZone);
            $leapMonth = $leapOff - 2;
            if ($leapMonth < 0) {
                $leapMonth += 12;
            }

            return $leapMonth;
        }

        return null;
    }

    /**
     * Compute Sexagenary (Can Chi) name for a given lunar year.
     */
    public function getYearCanChi(int $lunarYear): string
    {
        $canIndex = ($lunarYear + 6) % 10;
        if ($canIndex < 0) {
            $canIndex += 10;
        }

        $chiIndex = ($lunarYear + 8) % 12;
        if ($chiIndex < 0) {
            $chiIndex += 12;
        }

        return self::CAN[$canIndex].' '.self::CHI[$chiIndex];
    }

    /**
     * Compute Sexagenary (Can Chi) name for a given day (Julian Day Number).
     */
    public function getDayCanChi(int $jd): string
    {
        $canIndex = ($jd + 9) % 10;
        if ($canIndex < 0) {
            $canIndex += 10;
        }

        $chiIndex = ($jd + 1) % 12;
        if ($chiIndex < 0) {
            $chiIndex += 12;
        }

        return self::CAN[$canIndex].' '.self::CHI[$chiIndex];
    }

    /**
     * Compute Sexagenary (Can Chi) name for a given lunar month.
     */
    public function getMonthCanChi(int $lunarMonth, int $lunarYear): string
    {
        $yearCanIndex = ($lunarYear + 6) % 10;
        if ($yearCanIndex < 0) {
            $yearCanIndex += 10;
        }

        // Tháng Giêng (Tháng 1) Can is ($yearCanIndex * 2 + 2) % 10
        $monthCanIndex = ($yearCanIndex * 2 + 2 + ($lunarMonth - 1)) % 10;
        if ($monthCanIndex < 0) {
            $monthCanIndex += 10;
        }

        // Tháng 11 là Tý (0), Tháng 12 là Sửu (1), Tháng 1 là Dần (2), ..., Tháng 10 là Hợi (11)
        $monthChiIndex = ($lunarMonth + 1) % 12;

        return self::CAN[$monthCanIndex].' '.self::CHI[$monthChiIndex];
    }

    /**
     * Compute the Solar Term (Tiết khí) for a given Julian Day Number in the given timezone.
     */
    public function getSolarTerm(int $dayNumber, float $timeZone = 7.0): string
    {
        $sunLong = $this->getSunLongitude($dayNumber, $timeZone);
        $termIndex = (int) floor($sunLong / 15.0);
        if ($termIndex < 0 || $termIndex >= 24) {
            $termIndex = ($termIndex % 24 + 24) % 24;
        }

        return self::SOLAR_TERMS[$termIndex];
    }
}
