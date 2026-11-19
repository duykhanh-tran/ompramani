<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Enums\CalendarEventRecurrence;
use App\Enums\CalendarEventType;
use App\Enums\CalendarMissingDayPolicy;
use App\Enums\CalendarSystem;
use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Calendar Event Occurrence Engine.
 *
 * Automatically calculates exact Gregorian and Vietnamese Lunisolar event occurrences
 * from the immutable FixedCalendarEventRegistry and stored descriptions.
 * No manual event creation, date adjustments, or approval workflows.
 */
class CalendarEventOccurrenceService
{
    public const MAX_RANGE_DAYS = 366;

    public function __construct(
        protected VietnameseLunisolarCalendar $calendarEngine,
        protected CalendarService $calendarService
    ) {}

    /**
     * Get all event occurrences within a solar date range [from, to].
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ValidationException
     */
    public function getOccurrencesBetween(
        string $fromDate,
        string $toDate,
        ?string $traditionSlug = null,
        ?string $eventType = null,
        bool $onlyPublished = true
    ): Collection {
        $this->validateDateRange($fromDate, $toDate);

        // 1. Fetch stored descriptions from DB keyed by slug
        $dbEvents = CalendarEvent::query()
            ->with(['traditions', 'buddhistFigures'])
            ->get()
            ->keyBy('slug');

        // 2. Identify candidate solar and lunar years
        $minSolarYear = (int) substr($fromDate, 0, 4);
        $maxSolarYear = (int) substr($toDate, 0, 4);
        $solarYears = range($minSolarYear, $maxSolarYear);

        // For lunar years: January of solar year $Y can be month 11/12 of lunar year $Y - 1
        $minLunarYear = max(CalendarService::MIN_LUNAR_YEAR, $minSolarYear - 1);
        $maxLunarYear = min(CalendarService::MAX_LUNAR_YEAR, $maxSolarYear + 1);
        $lunarYears = range($minLunarYear, $maxLunarYear);

        $rawOccurrences = [];
        $registry = FixedCalendarEventRegistry::all();

        foreach ($registry as $key => $def) {
            // Filter by event_type if specified
            if ($eventType !== null && $def['event_type']->value !== $eventType) {
                continue;
            }

            /** @var CalendarEvent|null $dbEvent */
            $dbEvent = $dbEvents->get($key);

            $description = $dbEvent?->description ?? $def['default_description'] ?? null;
            $shortDescription = $dbEvent?->short_description ?? $def['default_short_description'] ?? null;
            $traditions = $dbEvent ? $dbEvent->traditions->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
            ])->values()->all() : [];

            $buddhistFigures = $dbEvent ? $dbEvent->buddhistFigures->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'slug' => $f->slug,
                'thumbnail' => $f->thumbnail,
            ])->values()->all() : [];

            $eventOccurrences = $this->calculateOccurrencesForDefinition(
                $def,
                $description,
                $shortDescription,
                $traditions,
                $buddhistFigures,
                $fromDate,
                $toDate,
                $solarYears,
                $lunarYears
            );

            foreach ($eventOccurrences as $occ) {
                $rawOccurrences[] = $occ;
            }
        }

        // Deduplicate and Sort
        return collect($rawOccurrences)
            ->unique('occurrence_id')
            ->sort(function (array $a, array $b): int {
                if ($a['solar_date'] !== $b['solar_date']) {
                    return strcmp($a['solar_date'], $b['solar_date']);
                }
                $priorityA = $a['event_type_priority'] ?? 99;
                $priorityB = $b['event_type_priority'] ?? 99;
                if ($priorityA !== $priorityB) {
                    return $priorityA <=> $priorityB;
                }
                $orderA = $a['sort_order'] ?? 0;
                $orderB = $b['sort_order'] ?? 0;
                if ($orderA !== $orderB) {
                    return $orderA <=> $orderB;
                }

                return strcmp((string) $a['title'], (string) $b['title']);
            })
            ->values();
    }

    /**
     * Preview calculated upcoming occurrences for a specific event (e.g. next 1-3 years).
     *
     * @return array<int, array<string, mixed>>
     */
    public function previewEventOccurrences(CalendarEvent $event, int $yearsAhead = 3): array
    {
        $currentYear = (int) CarbonImmutable::now(CalendarService::TIMEZONE)->format('Y');
        $fromDate = sprintf('%04d-01-01', $currentYear);
        $toDate = sprintf('%04d-12-31', $currentYear + $yearsAhead - 1);

        $def = FixedCalendarEventRegistry::get($event->slug);
        if ($def === null) {
            // Fallback for custom or legacy event
            return [];
        }

        $solarYears = range($currentYear, $currentYear + $yearsAhead - 1);
        $lunarYears = range(max(CalendarService::MIN_LUNAR_YEAR, $currentYear - 1), min(CalendarService::MAX_LUNAR_YEAR, $currentYear + $yearsAhead));

        $raw = $this->calculateOccurrencesForDefinition(
            $def,
            $event->description,
            $event->short_description,
            [],
            [],
            $fromDate,
            $toDate,
            $solarYears,
            $lunarYears
        );

        return array_map(function (array $occ): array {
            return [
                'solar_date' => $occ['solar_date'],
                'solar_weekday' => $occ['solar']['weekday_name'],
                'lunar_date' => sprintf('%02d/%02d/%04d%s', $occ['lunar']['day'], $occ['lunar']['month'], $occ['lunar']['year'], $occ['lunar']['is_leap_month'] ? ' (Nhuận)' : ''),
                'can_chi' => $occ['lunar']['year_can_chi'].' - '.$occ['lunar']['day_can_chi'],
                'solar_term' => $occ['lunar']['solar_term'],
            ];
        }, $raw);
    }

    /**
     * Calculate occurrences for a fixed event definition.
     *
     * @param  array<string, mixed>  $def
     * @param  array<int, mixed>  $traditions
     * @param  array<int, mixed>  $buddhistFigures
     * @param  array<int, int>  $solarYears
     * @param  array<int, int>  $lunarYears
     * @return array<int, array<string, mixed>>
     */
    protected function calculateOccurrencesForDefinition(
        array $def,
        ?string $description,
        ?string $shortDescription,
        array $traditions,
        array $buddhistFigures,
        string $fromDate,
        string $toDate,
        array $solarYears,
        array $lunarYears
    ): array {
        $occurrences = [];
        $dateRule = $def['date_rule'] ?? null;

        // 1. Gregorian Fixed Solar Events
        if ($dateRule === 'gregorian_fixed' || $def['recurrence_type'] === CalendarEventRecurrence::AnnualSolar) {
            $m = (int) $def['solar_month'];
            $d = (int) $def['solar_day'];

            foreach ($solarYears as $sy) {
                if (! checkdate($m, $d, $sy)) {
                    continue;
                }
                $dateStr = sprintf('%04d-%02d-%02d', $sy, $m, $d);
                if ($dateStr >= $fromDate && $dateStr <= $toDate) {
                    $occurrences[] = $this->buildOccurrence($def, $description, $shortDescription, $traditions, $buddhistFigures, $dateStr);
                }
            }

            return $occurrences;
        }

        // 2. Solar Term: Lập Xuân (Astronomically calculated via Solar Terms provider)
        if ($dateRule === 'solar_term_lap_xuan' || $def['recurrence_type'] === CalendarEventRecurrence::SolarTerm) {
            foreach ($solarYears as $sy) {
                $lapXuanDate = $this->resolveLapXuanSolarDate($sy);
                if ($lapXuanDate !== null && $lapXuanDate >= $fromDate && $lapXuanDate <= $toDate) {
                    $occurrences[] = $this->buildOccurrence($def, $description, $shortDescription, $traditions, $buddhistFigures, $lapXuanDate);
                }
            }

            return $occurrences;
        }

        // 3. Tết Nguyên Đán (01/01 Lunar)
        if ($dateRule === 'lunar_tet_nguyen_dan') {
            foreach ($lunarYears as $ly) {
                $solar = $this->calendarEngine->convertLunarToSolar(1, 1, $ly, false, CalendarService::TIMEZONE_OFFSET);
                if ($solar !== null && $solar['solar_date'] >= $fromDate && $solar['solar_date'] <= $toDate) {
                    $occurrences[] = $this->buildOccurrence($def, $description, $shortDescription, $traditions, $buddhistFigures, $solar['solar_date']);
                }
            }

            return $occurrences;
        }

        // 4. Monthly Thập Trai (Section 08 & 09)
        if ($dateRule === 'thap_trai_monthly' || $def['recurrence_type'] === CalendarEventRecurrence::MonthlyLunar) {
            foreach ($lunarYears as $ly) {
                $leapMonth = $this->calendarEngine->getYearLeapMonth($ly, CalendarService::TIMEZONE_OFFSET);

                for ($m = 1; $m <= 12; $m++) {
                    // Regular month
                    $regLen = $this->calendarEngine->getLunarMonthLength($m, $ly, false, CalendarService::TIMEZONE_OFFSET);
                    if ($regLen !== null) {
                        $days = ($regLen === 30)
                            ? [1, 8, 14, 15, 18, 23, 24, 28, 29, 30]
                            : [1, 8, 14, 15, 18, 23, 24, 27, 28, 29];

                        foreach ($days as $dayInt) {
                            $solar = $this->calendarEngine->convertLunarToSolar($dayInt, $m, $ly, false, CalendarService::TIMEZONE_OFFSET);
                            if ($solar !== null && $solar['solar_date'] >= $fromDate && $solar['solar_date'] <= $toDate) {
                                $occurrences[] = $this->buildOccurrence(
                                    $def,
                                    $description,
                                    $shortDescription,
                                    $traditions,
                                    $buddhistFigures,
                                    $solar['solar_date'],
                                    false
                                );
                            }
                        }
                    }

                    // Leap month (Thập trai applies to leap months with proper label per Section 09)
                    if ($leapMonth === $m) {
                        $leapLen = $this->calendarEngine->getLunarMonthLength($m, $ly, true, CalendarService::TIMEZONE_OFFSET);
                        if ($leapLen !== null) {
                            $leapDays = ($leapLen === 30)
                                ? [1, 8, 14, 15, 18, 23, 24, 28, 29, 30]
                                : [1, 8, 14, 15, 18, 23, 24, 27, 28, 29];

                            foreach ($leapDays as $dayInt) {
                                $solar = $this->calendarEngine->convertLunarToSolar($dayInt, $m, $ly, true, CalendarService::TIMEZONE_OFFSET);
                                if ($solar !== null && $solar['solar_date'] >= $fromDate && $solar['solar_date'] <= $toDate) {
                                    $occurrences[] = $this->buildOccurrence(
                                        $def,
                                        $description,
                                        $shortDescription,
                                        $traditions,
                                        $buddhistFigures,
                                        $solar['solar_date'],
                                        true
                                    );
                                }
                            }
                        }
                    }
                }
            }

            return $occurrences;
        }

        // 5. Annual Lunar Events (Section 06 & 10)
        if ($dateRule === 'lunar_annual' || $dateRule === 'last_day_of_lunar_month' || $def['recurrence_type'] === CalendarEventRecurrence::AnnualLunar) {
            $lm = (int) $def['lunar_month'];
            $ld = (int) $def['lunar_day'];
            $missingPolicy = $def['missing_day_policy'] ?? CalendarMissingDayPolicy::Skip;

            foreach ($lunarYears as $ly) {
                // Annual lunar events only occur in regular months; not duplicated in leap months (Section 09)
                $monthLength = $this->calendarEngine->getLunarMonthLength($lm, $ly, false, CalendarService::TIMEZONE_OFFSET);
                if ($monthLength === null) {
                    continue;
                }

                $targetDay = $ld;
                $isMissingDayAdjusted = false;
                $missingDayOriginalDay = null;

                // Month-end policy for day 30 (e.g. 30/07 and 30/09)
                if ($targetDay > $monthLength) {
                    if ($missingPolicy === CalendarMissingDayPolicy::LastDay && $targetDay === 30 && $monthLength === 29) {
                        $targetDay = 29;
                        $isMissingDayAdjusted = true;
                        $missingDayOriginalDay = 30;
                    } else {
                        continue; // Skip if month does not have this day
                    }
                }

                $solar = $this->calendarEngine->convertLunarToSolar($targetDay, $lm, $ly, false, CalendarService::TIMEZONE_OFFSET);
                if ($solar !== null && $solar['solar_date'] >= $fromDate && $solar['solar_date'] <= $toDate) {
                    $occurrences[] = $this->buildOccurrence(
                        $def,
                        $description,
                        $shortDescription,
                        $traditions,
                        $buddhistFigures,
                        $solar['solar_date'],
                        false,
                        $isMissingDayAdjusted,
                        $missingDayOriginalDay
                    );
                }
            }

            return $occurrences;
        }

        return $occurrences;
    }

    /**
     * Resolve the exact Gregorian date when Lập Xuân solar term begins for a given solar year.
     * Evaluated using astronomical solar longitude passing 315° in UTC+07:00.
     */
    public function resolveLapXuanSolarDate(int $solarYear): ?string
    {
        // Lập Xuân occurs in early February (Feb 3 - Feb 5)
        for ($d = 2; $d <= 7; $d++) {
            $jdCurrent = $this->calendarEngine->jdFromDate($d, 2, $solarYear);
            $jdPrev = $this->calendarEngine->jdFromDate($d - 1, 2, $solarYear);

            $termCurrent = $this->calendarEngine->getSolarTerm($jdCurrent, CalendarService::TIMEZONE_OFFSET);
            $termPrev = $this->calendarEngine->getSolarTerm($jdPrev, CalendarService::TIMEZONE_OFFSET);

            if ($termCurrent === 'Lập xuân' && $termPrev !== 'Lập xuân') {
                return sprintf('%04d-02-%02d', $solarYear, $d);
            }
        }

        return null;
    }

    /**
     * Build full occurrence data payload.
     *
     * @param  array<string, mixed>  $def
     * @param  array<int, mixed>  $traditions
     * @param  array<int, mixed>  $buddhistFigures
     * @return array<string, mixed>
     */
    protected function buildOccurrence(
        array $def,
        ?string $description,
        ?string $shortDescription,
        array $traditions,
        array $buddhistFigures,
        string $solarDate,
        bool $isLeap = false,
        bool $isMissingDayAdjusted = false,
        ?int $missingDayOriginalDay = null
    ): array {
        [$sy, $sm, $sd] = array_map('intval', explode('-', $solarDate));
        $lunar = $this->calendarEngine->convertSolarToLunar($sd, $sm, $sy, CalendarService::TIMEZONE_OFFSET);

        $dateObj = new DateTimeImmutable($solarDate);
        $weekdayIso = (int) $dateObj->format('N');

        /** @var CalendarEventType $eventType */
        $eventType = $def['event_type'];
        $occurrenceId = sprintf('%s-%s%s', $def['key'], $solarDate, $isLeap ? '-leap' : '');

        return [
            'occurrence_id' => $occurrenceId,
            'event_key' => $def['key'],
            'event_id' => $def['key'],
            'slug' => $def['key'],
            'title' => $def['title'],
            'short_description' => $shortDescription,
            'description' => $description,
            'event_type' => $eventType->value,
            'event_type_label' => $eventType->label(),
            'event_type_color' => $eventType->color(),
            'event_type_priority' => $eventType->priority(),
            'calendar_system' => $def['calendar_system']->value,
            'recurrence_type' => $def['recurrence_type']->value,
            'solar_date' => $solarDate,
            'solar_day' => $sd,
            'solar_month' => $sm,
            'solar_year' => $sy,
            'lunar_date' => sprintf('%04d-%02d-%02d', $lunar['lunar_year'], $lunar['lunar_month'], $lunar['lunar_day']),
            'lunar_day' => $lunar['lunar_day'],
            'lunar_month' => $lunar['lunar_month'],
            'lunar_year' => $lunar['lunar_year'],
            'lunar_is_leap_month' => $lunar['is_leap_month'],
            'solar' => [
                'year' => $sy,
                'month' => $sm,
                'day' => $sd,
                'weekday_iso' => $weekdayIso,
                'weekday_name' => VietnameseLunisolarCalendar::WEEKDAYS[$weekdayIso],
            ],
            'lunar' => [
                'year' => $lunar['lunar_year'],
                'month' => $lunar['lunar_month'],
                'day' => $lunar['lunar_day'],
                'is_leap_month' => $lunar['is_leap_month'],
                'month_length' => $lunar['lunar_month_length'],
                'year_can_chi' => $lunar['year_can_chi'],
                'day_can_chi' => $lunar['day_can_chi'],
                'solar_term' => $lunar['solar_term'],
            ],
            'is_general' => true,
            'is_thap_trai' => ($def['key'] === 'thap_trai'),
            'sort_order' => $def['sort_order'] ?? 0,
            'traditions' => $traditions,
            'buddhist_figures' => $buddhistFigures,
            'is_exception_adjusted' => false,
            'missing_day_adjusted' => $isMissingDayAdjusted,
            'missing_day_original_day' => $missingDayOriginalDay,
        ];
    }

    /**
     * Validate solar date range strictly.
     *
     * @throws ValidationException
     */
    protected function validateDateRange(string $from, string $to): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            throw ValidationException::withMessages([
                'range' => ['Định dạng ngày không hợp lệ. Vui lòng sử dụng YYYY-MM-DD.'],
            ]);
        }

        if ($from > $to) {
            throw ValidationException::withMessages([
                'range' => ['Ngày bắt đầu (from) không được lớn hơn ngày kết thúc (to).'],
            ]);
        }

        $fromDateObj = new DateTimeImmutable($from);
        $toDateObj = new DateTimeImmutable($to);
        $diff = $fromDateObj->diff($toDateObj)->days;

        if ($diff > self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'range' => [sprintf('Khoảng cách giữa hai ngày không được vượt quá %d ngày.', self::MAX_RANGE_DAYS)],
            ]);
        }
    }
}
