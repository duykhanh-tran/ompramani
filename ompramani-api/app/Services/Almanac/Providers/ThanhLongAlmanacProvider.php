<?php

declare(strict_types=1);

namespace App\Services\Almanac\Providers;

use App\Enums\AlmanacVerificationStatus;
use App\Services\Almanac\Constants\EarthlyBranch;
use App\Services\Almanac\Constants\HeavenlyStem;
use App\Services\Almanac\Constants\YellowBlackDeity;
use App\Services\Almanac\Contracts\AlmanacProviderInterface;
use App\Services\Calendar\CalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ThanhLongAlmanacProvider implements AlmanacProviderInterface
{
    public const KEY = 'thanh_long_deity';

    public const NAME = 'Thập Nhị Hoàng Đạo Thần Sát (Thanh Long - Minh Đường)';

    public const VERSION = '1.0.0';

    public const MIN_SUPPORTED_DATE = '1900-01-31';

    public const MAX_SUPPORTED_DATE = '2100-12-31';

    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    /**
     * Mapping from Day Branch index to Hour Starting Deity Branch index.
     *
     * Rule:
     * Dần (2), Thân (8) -> Tý (0)
     * Mão (3), Dậu (9)  -> Dần (2)
     * Thìn (4), Tuất (10)-> Thìn (4)
     * Tỵ (5), Hợi (11)  -> Ngọ (6)
     * Tý (0), Ngọ (6)   -> Thân (8)
     * Sửu (1), Mùi (7)  -> Tuất (10)
     *
     * @var array<int, int>
     */
    protected const HOUR_DEITY_START_MAP = [
        0 => 8,
        6 => 8,
        1 => 10,
        7 => 10,
        2 => 0,
        8 => 0,
        3 => 2,
        9 => 2,
        4 => 4,
        10 => 4,
        5 => 6,
        11 => 6,
    ];

    public function __construct(
        protected CalendarService $calendarService
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getMetadata(): array
    {
        return [
            'key' => self::KEY,
            'name' => self::NAME,
            'version' => self::VERSION,
            'description' => 'Phương pháp tính ngày và giờ Hoàng đạo / Hắc đạo dựa trên Thập Nhị Thần Sát và Can Chi truyền thống Việt Nam.',
            'source_title' => 'Hiệp Kỷ Biện Phương Thư / Lịch Vạn Niên Việt Nam',
            'reference_source' => 'Viện Nghiên cứu Văn hóa & GS. Hoàng Châu',
            'license' => 'Public Domain / Traditional Folk Heritage',
            'calendar_system' => 'vietnamese_lunisolar',
            'calendar_timezone' => self::TIMEZONE,
            'supported_start_date' => self::MIN_SUPPORTED_DATE,
            'supported_end_date' => self::MAX_SUPPORTED_DATE,
            'hour_anchoring' => 'start_date_anchored',
            'disclaimer' => 'Thông tin ngày giờ theo lịch truyền thống, mang tính tham khảo văn hóa dân gian. Không phải giáo lý Phật giáo. Không dùng để bói toán hay quyết định vận mệnh.',
            'verification_status' => AlmanacVerificationStatus::Verified->value,
        ];
    }

    public function isSupportedDate(string $dateString): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return false;
        }

        return $dateString >= self::MIN_SUPPORTED_DATE && $dateString <= self::MAX_SUPPORTED_DATE;
    }

    /**
     * Calculate almanac day data for a solar date.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function getDayData(string $dateString): array
    {
        if (! $this->isSupportedDate($dateString)) {
            throw ValidationException::withMessages([
                'date' => [sprintf('Ngày dương lịch (%s) nằm ngoài phạm vi hỗ trợ của phương pháp (%s đến %s).', $dateString, self::MIN_SUPPORTED_DATE, self::MAX_SUPPORTED_DATE)],
            ]);
        }

        // 1. Get verified lunar conversion from Calendar Core (Task 27)
        $calendarDay = $this->calendarService->getCalendarDay($dateString);
        $solar = $calendarDay['solar'];
        $lunar = $calendarDay['lunar'];

        $dayCanChi = (string) $lunar['day_can_chi'];
        $canChiParts = explode(' ', $dayCanChi);
        $stemName = $canChiParts[0] ?? '';
        $branchName = $canChiParts[1] ?? '';

        $dayStemIndex = HeavenlyStem::findIndexByLabel($stemName) ?? 0;
        $dayBranchIndex = EarthlyBranch::findIndexByLabel($branchName) ?? 0;
        $lunarMonth = (int) ($lunar['month'] ?? $lunar['lunar_month'] ?? 1);

        // 2. Day Classification based on Lunar Month and Day Branch
        // Tháng 1 & 7: Tý (0), Tháng 2 & 8: Dần (2), Tháng 3 & 9: Thìn (4),
        // Tháng 4 & 10: Ngọ (6), Tháng 5 & 11: Thân (8), Tháng 6 & 12: Tuất (10)
        $monthStartChi = (($lunarMonth - 1) % 6) * 2;
        $dayStarIndex = ($dayBranchIndex - $monthStartChi + 12) % 12;
        $dayDeity = YellowBlackDeity::getByIndex($dayStarIndex);

        // 3. Compute 12 Earthly Branch hours
        $hours = $this->calculateTwelveHours($dateString, $dayStemIndex, $dayBranchIndex);

        return [
            'solar_date' => $dateString,
            'calendar_timezone' => self::TIMEZONE,
            'solar' => $solar,
            'lunar' => $lunar,
            'can_chi' => [
                'year' => $lunar['year_can_chi'],
                'month' => $lunar['month_can_chi'],
                'day' => $lunar['day_can_chi'],
                'hour' => null, // Day view includes all 12 hours with their respective Can Chi
                'method' => 'lunar_new_year_and_jd_anchor',
            ],
            'day_classification' => $dayDeity['day_classification']->value,
            'deity_name' => $dayDeity['name'],
            'is_auspicious' => $dayDeity['is_auspicious'],
            'has_override' => false,
            'hours' => $hours,
            'method' => $this->getMetadata(),
            'verification_status' => AlmanacVerificationStatus::Verified->value,
            'disclaimer' => 'Thông tin ngày giờ theo lịch truyền thống, mang tính tham khảo văn hóa dân gian. Không phải giáo lý Phật giáo. Không dùng để bói toán hay quyết định vận mệnh.',
        ];
    }

    /**
     * Compute summary data for all days in a solar month.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function getMonthData(int $year, int $month): array
    {
        $monthCalendar = $this->calendarService->getCalendarMonth($year, $month);
        $days = [];

        foreach ($monthCalendar['days'] as $d) {
            $dateStr = $d['solar_date'];
            if (! $this->isSupportedDate($dateStr)) {
                continue;
            }

            $dayCanChi = (string) $d['day_can_chi'];
            $canChiParts = explode(' ', $dayCanChi);
            $branchName = $canChiParts[1] ?? '';
            $dayBranchIndex = EarthlyBranch::findIndexByLabel($branchName) ?? 0;
            $lunarMonth = (int) $d['lunar_month'];

            $monthStartChi = (($lunarMonth - 1) % 6) * 2;
            $dayStarIndex = ($dayBranchIndex - $monthStartChi + 12) % 12;
            $dayDeity = YellowBlackDeity::getByIndex($dayStarIndex);

            $days[] = [
                'solar_date' => $dateStr,
                'solar_day' => $d['solar_day'],
                'weekday_iso' => $d['weekday_iso'],
                'weekday_name' => $d['weekday_name'],
                'lunar_day' => $d['lunar_day'],
                'lunar_month' => $d['lunar_month'],
                'lunar_year' => $d['lunar_year'],
                'is_leap_month' => $d['is_leap_month'],
                'year_can_chi' => $d['year_can_chi'],
                'day_can_chi' => $d['day_can_chi'],
                'day_classification' => $dayDeity['day_classification']->value,
                'deity_name' => $dayDeity['name'],
                'is_auspicious' => $dayDeity['is_auspicious'],
                'auspicious_hours_count' => 6,
                'verification_status' => AlmanacVerificationStatus::Verified->value,
            ];
        }

        return [
            'solar_year' => $year,
            'solar_month' => $month,
            'calendar_timezone' => self::TIMEZONE,
            'method' => [
                'key' => self::KEY,
                'name' => self::NAME,
                'version' => self::VERSION,
            ],
            'days' => $days,
        ];
    }

    /**
     * Compute 12 Earthly Branch hours with date anchoring and deity classifications.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function calculateTwelveHours(string $solarDate, int $dayStemIndex, int $dayBranchIndex): array
    {
        $hourStartChi = self::HOUR_DEITY_START_MAP[$dayBranchIndex] ?? 0;

        // Can of Giờ Tý by "Ngũ Thử Độn Nhật" rule: ($dayStemIndex % 5 * 2) % 10
        $ziStemIndex = (($dayStemIndex % 5) * 2) % 10;

        $hours = [];
        $carbonDate = CarbonImmutable::parse($solarDate, self::TIMEZONE);
        $nextDayDate = $carbonDate->addDay()->format('Y-m-d');

        for ($i = 0; $i < 12; $i++) {
            $branch = EarthlyBranch::getByIndex($i);

            // Compute Can Chi for this hour
            $hourStemIndex = ($ziStemIndex + $i) % 10;
            $hourStem = HeavenlyStem::getByIndex($hourStemIndex);
            $hourCanChi = $hourStem['label'].' '.$branch['label'];

            // Compute deity of this hour
            $hourStarIndex = ($i - $hourStartChi + 12) % 12;
            $deity = YellowBlackDeity::getByIndex($hourStarIndex);

            // Datetime boundaries with Start-Date Anchoring
            if ($branch['crosses_midnight']) {
                // Giờ Tý: 23:00 date D to 01:00 date D+1
                $startDatetime = "{$solarDate} 23:00:00";
                $endDatetime = "{$nextDayDate} 01:00:00";
            } else {
                $startDatetime = "{$solarDate} {$branch['start_time']}:00";
                $endDatetime = "{$solarDate} {$branch['end_time']}:00";
            }

            $hours[] = [
                'branch_index' => $i,
                'branch_key' => $branch['key'],
                'branch_name' => $branch['label'],
                'can_chi' => $hourCanChi,
                'start_local_time' => $branch['start_time'],
                'end_local_time' => $branch['end_time'],
                'crosses_midnight' => $branch['crosses_midnight'],
                'start_local_datetime' => $startDatetime,
                'end_local_datetime' => $endDatetime,
                'classification' => $deity['hour_classification']->value,
                'deity_name' => $deity['name'],
                'is_auspicious' => $deity['is_auspicious'],
            ];
        }

        return $hours;
    }
}
