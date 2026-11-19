<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Validation\ValidationException;

class CalendarService
{
    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public const TIMEZONE_OFFSET = 7.0;

    public const MIN_SOLAR_DATE = '1900-01-31';

    public const MAX_SOLAR_DATE = '2100-12-31';

    public const MIN_LUNAR_YEAR = 1900;

    public const MAX_LUNAR_YEAR = 2100;

    public function __construct(
        protected VietnameseLunisolarCalendar $calendarEngine
    ) {}

    /**
     * Convert Gregorian solar date (YYYY-MM-DD) to Vietnamese lunar date.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function solarToLunar(string $dateString): array
    {
        $this->validateSolarDateString($dateString);

        [$year, $month, $day] = array_map('intval', explode('-', $dateString));
        $lunar = $this->calendarEngine->convertSolarToLunar($day, $month, $year, self::TIMEZONE_OFFSET);

        $dateObj = new DateTimeImmutable($dateString);
        $weekdayIso = (int) $dateObj->format('N');

        return [
            'solar_date' => $dateString,
            'solar' => [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'weekday_iso' => $weekdayIso,
                'weekday_name' => VietnameseLunisolarCalendar::WEEKDAYS[$weekdayIso],
            ],
            'lunar' => [
                'year' => $lunar['lunar_year'],
                'month' => $lunar['lunar_month'],
                'day' => $lunar['lunar_day'],
                'is_leap_month' => $lunar['is_leap_month'],
                'month_length' => $lunar['lunar_month_length'],
                'is_leap_year' => $lunar['is_lunar_leap_year'],
                'leap_month' => $lunar['leap_month_number'],
                'year_can_chi' => $lunar['year_can_chi'],
                'day_can_chi' => $lunar['day_can_chi'],
                'month_can_chi' => $lunar['month_can_chi'],
                'solar_term' => $lunar['solar_term'],
            ],
            'calendar' => [
                'system' => 'vietnamese_lunisolar',
                'timezone' => self::TIMEZONE,
            ],
        ];
    }

    /**
     * Convert Vietnamese lunar date to Gregorian solar date.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function lunarToSolar(int $year, int $month, int $day, bool $isLeapMonth): array
    {
        $this->validateLunarInput($year, $month, $day, $isLeapMonth);

        $solar = $this->calendarEngine->convertLunarToSolar($day, $month, $year, $isLeapMonth, self::TIMEZONE_OFFSET);

        if ($solar === null) {
            throw ValidationException::withMessages([
                'date' => ['Không thể chuyển đổi ngày âm lịch đã cung cấp sang ngày dương lịch hợp lệ.'],
            ]);
        }

        if ($solar['solar_date'] < self::MIN_SOLAR_DATE || $solar['solar_date'] > self::MAX_SOLAR_DATE) {
            throw ValidationException::withMessages([
                'date' => [sprintf('Ngày dương lịch tương ứng (%s) nằm ngoài phạm vi được hỗ trợ (%s đến %s).', $solar['solar_date'], self::MIN_SOLAR_DATE, self::MAX_SOLAR_DATE)],
            ]);
        }

        $dateObj = new DateTimeImmutable($solar['solar_date']);
        $weekdayIso = (int) $dateObj->format('N');
        $monthLength = $this->calendarEngine->getLunarMonthLength($month, $year, $isLeapMonth, self::TIMEZONE_OFFSET);
        $yearCanChi = $this->calendarEngine->getYearCanChi($year);

        return [
            'lunar' => [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'is_leap_month' => $isLeapMonth,
                'month_length' => $monthLength,
                'year_can_chi' => $yearCanChi,
            ],
            'solar_date' => $solar['solar_date'],
            'solar' => [
                'year' => $solar['solar_year'],
                'month' => $solar['solar_month'],
                'day' => $solar['solar_day'],
                'weekday_iso' => $weekdayIso,
                'weekday_name' => VietnameseLunisolarCalendar::WEEKDAYS[$weekdayIso],
            ],
            'calendar' => [
                'system' => 'vietnamese_lunisolar',
                'timezone' => self::TIMEZONE,
            ],
        ];
    }

    /**
     * Get comprehensive calendar data for a specific day (default: today in Asia/Ho_Chi_Minh).
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function getCalendarDay(?string $dateString = null): array
    {
        if ($dateString === null || trim($dateString) === '') {
            $dateString = CarbonImmutable::now(self::TIMEZONE)->format('Y-m-d');
        }

        $this->validateSolarDateString($dateString);

        [$year, $month, $day] = array_map('intval', explode('-', $dateString));
        $lunar = $this->calendarEngine->convertSolarToLunar($day, $month, $year, self::TIMEZONE_OFFSET);

        $dateObj = new DateTimeImmutable($dateString);
        $weekdayIso = (int) $dateObj->format('N');

        $isThapTrai = ($lunar['lunar_month_length'] === 30)
            ? in_array($lunar['lunar_day'], [1, 8, 14, 15, 18, 23, 24, 28, 29, 30], true)
            : in_array($lunar['lunar_day'], [1, 8, 14, 15, 18, 23, 24, 27, 28, 29], true);

        return [
            'solar' => [
                'date' => $dateString,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'weekday_iso' => $weekdayIso,
                'weekday_name' => VietnameseLunisolarCalendar::WEEKDAYS[$weekdayIso],
            ],
            'lunar' => [
                'year' => $lunar['lunar_year'],
                'month' => $lunar['lunar_month'],
                'day' => $lunar['lunar_day'],
                'is_leap_month' => $lunar['is_leap_month'],
                'month_length' => $lunar['lunar_month_length'],
                'is_leap_year' => $lunar['is_lunar_leap_year'],
                'leap_month' => $lunar['leap_month_number'],
                'year_can_chi' => $lunar['year_can_chi'],
                'day_can_chi' => $lunar['day_can_chi'],
                'month_can_chi' => $lunar['month_can_chi'],
                'solar_term' => $lunar['solar_term'],
                'is_thap_trai' => $isThapTrai,
            ],
            'is_thap_trai' => $isThapTrai,
            'calendar_timezone' => self::TIMEZONE,
        ];
    }

    /**
     * Get calendar data for a whole solar month.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function getCalendarMonth(int $year, int $month): array
    {
        if ($year < 1900 || $year > 2100) {
            throw ValidationException::withMessages([
                'year' => [sprintf('Năm phải nằm trong khoảng từ 1900 đến 2100.')],
            ]);
        }

        if ($month < 1 || $month > 12) {
            throw ValidationException::withMessages([
                'month' => ['Tháng phải nằm trong khoảng từ 1 đến 12.'],
            ]);
        }

        $firstDayString = sprintf('%04d-%02d-01', $year, $month);
        $firstDateObj = new DateTimeImmutable($firstDayString);
        $daysInMonth = (int) $firstDateObj->format('t');
        $firstWeekdayIso = (int) $firstDateObj->format('N');

        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);

            // Check if current date is within min/max bounds
            if ($currentDateStr < self::MIN_SOLAR_DATE || $currentDateStr > self::MAX_SOLAR_DATE) {
                continue;
            }

            $currentObj = new DateTimeImmutable($currentDateStr);
            $weekdayIso = (int) $currentObj->format('N');
            $lunar = $this->calendarEngine->convertSolarToLunar($d, $month, $year, self::TIMEZONE_OFFSET);

            $isThapTrai = ($lunar['lunar_month_length'] === 30)
                ? in_array($lunar['lunar_day'], [1, 8, 14, 15, 18, 23, 24, 28, 29, 30], true)
                : in_array($lunar['lunar_day'], [1, 8, 14, 15, 18, 23, 24, 27, 28, 29], true);

            $days[] = [
                'solar_date' => $currentDateStr,
                'solar_day' => $d,
                'weekday_iso' => $weekdayIso,
                'weekday_name' => VietnameseLunisolarCalendar::WEEKDAYS[$weekdayIso],
                'lunar_day' => $lunar['lunar_day'],
                'lunar_month' => $lunar['lunar_month'],
                'lunar_year' => $lunar['lunar_year'],
                'is_leap_month' => $lunar['is_leap_month'],
                'year_can_chi' => $lunar['year_can_chi'],
                'day_can_chi' => $lunar['day_can_chi'],
                'solar_term' => $lunar['solar_term'],
                'is_lunar_first_day' => $lunar['lunar_day'] === 1,
                'is_full_moon' => $lunar['lunar_day'] === 15,
                'is_thap_trai' => $isThapTrai,
            ];
        }

        return [
            'solar_year' => $year,
            'solar_month' => $month,
            'calendar_timezone' => self::TIMEZONE,
            'first_weekday_iso' => $firstWeekdayIso,
            'days_in_month' => $daysInMonth,
            'days' => $days,
        ];
    }

    /**
     * Get 7-day weekly calendar data around a reference date (default: today in Asia/Ho_Chi_Minh).
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function getCalendarWeek(?string $dateString = null): array
    {
        if ($dateString === null || trim($dateString) === '') {
            $dateString = CarbonImmutable::now(self::TIMEZONE)->format('Y-m-d');
        }

        $this->validateSolarDateString($dateString);

        $targetDate = new DateTimeImmutable($dateString, new \DateTimeZone(self::TIMEZONE));
        $targetDayOfWeek = (int) $targetDate->format('N'); // 1 = Monday, 7 = Sunday
        $mondayOffset = 1 - $targetDayOfWeek;
        $monday = $targetDate->modify("{$mondayOffset} days");

        $todayStr = CarbonImmutable::now(self::TIMEZONE)->format('Y-m-d');

        $shortWeekdays = [
            1 => 'Thứ 2',
            2 => 'Thứ 3',
            3 => 'Thứ 4',
            4 => 'Thứ 5',
            5 => 'Thứ 6',
            6 => 'Thứ 7',
            7 => 'Chủ Nhật',
        ];

        $days = [];
        $selectedDayIndex = 0;

        for ($i = 0; $i < 7; $i++) {
            $cur = $monday->modify("+{$i} days");
            $curDateStr = $cur->format('Y-m-d');
            [$y, $m, $d] = array_map('intval', explode('-', $curDateStr));

            $lunar = $this->calendarEngine->convertSolarToLunar($d, $m, $y, self::TIMEZONE_OFFSET);
            $weekdayIso = (int) $cur->format('N');
            $isToday = ($curDateStr === $todayStr);
            $isSunday = ($weekdayIso === 7);

            if ($curDateStr === $dateString) {
                $selectedDayIndex = $i;
            }

            $tag = null;
            $tagType = null;

            if ($isToday) {
                $tag = 'Hôm nay';
                $tagType = 'today';
            } elseif ($lunar['lunar_day'] === 1) {
                $tag = 'Mùng 1';
                $tagType = 'first_day';
            } elseif ($lunar['lunar_day'] === 15) {
                $tag = 'Rằm';
                $tagType = 'veg';
            } elseif (in_array($lunar['lunar_day'], [14, 29, 30], true)) {
                if ($lunar['lunar_day'] === 30 || ($lunar['lunar_day'] === 29 && $lunar['lunar_month_length'] === 29) || $lunar['lunar_day'] === 14) {
                    $tag = 'Ăn chay';
                    $tagType = 'veg';
                }
            }

            $lunarDisplay = sprintf('%02d', $lunar['lunar_day']);
            if ($isToday) {
                $lunarDisplay = sprintf('%02d/%02d AL', $lunar['lunar_day'], $lunar['lunar_month']);
            } elseif ($lunar['lunar_day'] === 1 || $lunar['lunar_day'] === 15) {
                $lunarDisplay = sprintf('%02d/%02d', $lunar['lunar_day'], $lunar['lunar_month']);
            }

            // Determine auspicious detail
            $auspiciousDetail = sprintf('Hôm nay: Ngày %s • Tiết %s', $lunar['day_can_chi'], $lunar['solar_term']);
            if ($lunar['lunar_day'] === 1) {
                $auspiciousDetail = 'Hôm nay: Ngày Sóc (Mùng 1) • Ăn chay';
            } elseif ($lunar['lunar_day'] === 15) {
                $auspiciousDetail = 'Hôm nay: Ngày Vọng (Rằm) • Ăn chay';
            } elseif ($tag === 'Ăn chay') {
                $auspiciousDetail = 'Hôm nay: Ngày Thọ Trai • Ăn chay thanh tịnh';
            }

            $days[] = [
                'solar_date' => $curDateStr,
                'solar_day' => sprintf('%02d', $d),
                'day_of_week' => $shortWeekdays[$weekdayIso],
                'weekday_iso' => $weekdayIso,
                'weekday_name' => VietnameseLunisolarCalendar::WEEKDAYS[$weekdayIso],
                'lunar_day' => $lunar['lunar_day'],
                'lunar_month' => $lunar['lunar_month'],
                'lunar_year' => $lunar['lunar_year'],
                'is_leap_month' => $lunar['is_leap_month'],
                'lunar_display' => $lunarDisplay,
                'is_today' => $isToday,
                'is_sunday' => $isSunday,
                'year_can_chi' => $lunar['year_can_chi'],
                'day_can_chi' => $lunar['day_can_chi'],
                'month_can_chi' => $lunar['month_can_chi'],
                'solar_term' => $lunar['solar_term'],
                'tag' => $tag,
                'tag_type' => $tagType,
                'auspicious_detail' => $auspiciousDetail,
            ];
        }

        return [
            'today' => $todayStr,
            'selected_date' => $dateString,
            'selected_index' => $selectedDayIndex,
            'calendar_timezone' => self::TIMEZONE,
            'days' => $days,
        ];
    }

    /**
     * Get system configuration and capability metadata.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [
            'system' => 'vietnamese_lunisolar',
            'calendar_timezone' => self::TIMEZONE,
            'timezone_offset' => '+07:00',
            'min_supported_solar_date' => self::MIN_SOLAR_DATE,
            'max_supported_solar_date' => self::MAX_SOLAR_DATE,
            'min_supported_lunar_year' => self::MIN_LUNAR_YEAR,
            'max_supported_lunar_year' => self::MAX_LUNAR_YEAR,
            'algorithm' => [
                'name' => 'Ho Ngoc Duc / Jean Meeus Astronomical Algorithms (AA98)',
                'reference_meridian' => '105°E (UTC+7:00)',
                'license' => 'Public Domain / Free Software',
            ],
            'capabilities' => [
                'solar_to_lunar',
                'lunar_to_solar',
                'day_view',
                'month_view',
                'leap_month',
                'can_chi',
                'solar_terms',
            ],
        ];
    }

    /**
     * Validate solar date string strictly.
     *
     * @throws ValidationException
     */
    protected function validateSolarDateString(string $dateString): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            throw ValidationException::withMessages([
                'date' => ['Định dạng ngày không hợp lệ. Vui lòng sử dụng định dạng YYYY-MM-DD.'],
            ]);
        }

        [$year, $month, $day] = array_map('intval', explode('-', $dateString));

        if (! checkdate($month, $day, $year)) {
            throw ValidationException::withMessages([
                'date' => [sprintf('Ngày %s không tồn tại trong lịch dương.', $dateString)],
            ]);
        }

        if ($dateString < self::MIN_SOLAR_DATE || $dateString > self::MAX_SOLAR_DATE) {
            throw ValidationException::withMessages([
                'date' => [sprintf('Ngày dương lịch (%s) nằm ngoài phạm vi được hỗ trợ (%s đến %s).', $dateString, self::MIN_SOLAR_DATE, self::MAX_SOLAR_DATE)],
            ]);
        }
    }

    /**
     * Validate lunar parameters strictly.
     *
     * @throws ValidationException
     */
    protected function validateLunarInput(int $year, int $month, int $day, bool $isLeapMonth): void
    {
        if ($year < self::MIN_LUNAR_YEAR || $year > self::MAX_LUNAR_YEAR) {
            throw ValidationException::withMessages([
                'year' => [sprintf('Năm âm lịch (%d) nằm ngoài phạm vi được hỗ trợ (%d đến %d).', $year, self::MIN_LUNAR_YEAR, self::MAX_LUNAR_YEAR)],
            ]);
        }

        if ($month < 1 || $month > 12) {
            throw ValidationException::withMessages([
                'month' => ['Tháng âm lịch phải từ 1 đến 12.'],
            ]);
        }

        $yearLeapMonth = $this->calendarEngine->getYearLeapMonth($year, self::TIMEZONE_OFFSET);
        if ($isLeapMonth) {
            if ($yearLeapMonth === null) {
                throw ValidationException::withMessages([
                    'is_leap_month' => [sprintf('Năm âm lịch %d không phải là năm nhuận.', $year)],
                ]);
            }
            if ($yearLeapMonth !== $month) {
                throw ValidationException::withMessages([
                    'is_leap_month' => [sprintf('Năm âm lịch %d có tháng %d nhuận, không phải tháng %d nhuận.', $year, $yearLeapMonth, $month)],
                ]);
            }
        }

        $monthLength = $this->calendarEngine->getLunarMonthLength($month, $year, $isLeapMonth, self::TIMEZONE_OFFSET);
        if ($monthLength === null) {
            throw ValidationException::withMessages([
                'month' => ['Không thể xác định độ dài tháng âm lịch.'],
            ]);
        }

        if ($day < 1 || $day > $monthLength) {
            throw ValidationException::withMessages([
                'day' => [sprintf('Ngày %d không hợp lệ. Tháng %d%s năm %d chỉ có %d ngày.', $day, $month, $isLeapMonth ? ' nhuận' : '', $year, $monthLength)],
            ]);
        }
    }
}
