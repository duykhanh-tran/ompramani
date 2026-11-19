<?php

namespace App\Services\Practice;

use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Validation\ValidationException;

class PracticeStreakService
{
    /**
     * Supported calendar days windows.
     */
    public const ALLOWED_DAYS = [7, 30, 90];

    /**
     * Fallback timezone identifier.
     */
    public const DEFAULT_TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function __construct(
        protected PracticeStreakEligibilityPolicy $eligibilityPolicy
    ) {}

    /**
     * Resolve and validate timezone preference.
     *
     * @throws ValidationException
     */
    public function resolveTimezone(?User $user, ?string $timezone): string
    {
        if ($timezone !== null && trim($timezone) !== '') {
            $trimmed = trim($timezone);
            try {
                return (new DateTimeZone($trimmed))->getName();
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'timezone' => "Múi giờ '{$timezone}' không hợp lệ theo chuẩn IANA.",
                ]);
            }
        }

        if ($user && $user->profile && ! empty($user->profile->timezone)) {
            $userTz = trim($user->profile->timezone);
            try {
                return (new DateTimeZone($userTz))->getName();
            } catch (\Throwable) {
                // fall through to default
            }
        }

        return self::DEFAULT_TIMEZONE;
    }

    /**
     * Resolve and validate calendar days window.
     *
     * @throws ValidationException
     */
    public function resolveDays(int|string|null $days): int
    {
        if ($days === null || trim((string) $days) === '') {
            return 30;
        }

        $intDays = (int) $days;
        if (! in_array($intDays, self::ALLOWED_DAYS, true)) {
            throw ValidationException::withMessages([
                'days' => 'Số ngày hiển thị lịch không hợp lệ. Chỉ chấp nhận: 7, 30, 90.',
            ]);
        }

        return $intDays;
    }

    /**
     * Calculate comprehensive streak data for the authenticated user.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function calculateStreak(User $user, int|string|null $days = 30, ?string $timezone = null): array
    {
        $resolvedTz = $this->resolveTimezone($user, $timezone);
        $resolvedDays = $this->resolveDays($days);

        // Fetch completed sessions with completion timestamps and metrics
        $sessions = PracticeSession::query()
            ->where('user_id', $user->id)
            ->where('status', PracticeSessionStatus::Completed->value)
            ->where('practice_type', '!=', PracticeType::BellWoodenFish->value)
            ->whereNotNull('ended_at')
            ->orderBy('ended_at', 'asc')
            ->select(['id', 'practice_type', 'status', 'active_duration_seconds', 'progress_data', 'ended_at'])
            ->get();

        // Group qualifying sessions by local date in user's timezone
        $distinctDatesCounts = [];
        foreach ($sessions as $session) {
            if (! $this->eligibilityPolicy->isQualifyingSession($session)) {
                continue;
            }

            $localDateStr = $session->ended_at->setTimezone($resolvedTz)->format('Y-m-d');
            $distinctDatesCounts[$localDateStr] = ($distinctDatesCounts[$localDateStr] ?? 0) + 1;
        }

        $sortedAscDates = array_keys($distinctDatesCounts);
        sort($sortedAscDates);

        // Local today & yesterday
        $nowLocal = CarbonImmutable::now($resolvedTz);
        $todayLocal = $nowLocal->startOfDay();
        $todayStr = $todayLocal->format('Y-m-d');
        $yesterdayStr = $todayLocal->subDays(1)->format('Y-m-d');

        $hasQualifiedToday = isset($distinctDatesCounts[$todayStr]);
        $hasQualifiedYesterday = isset($distinctDatesCounts[$yesterdayStr]);
        $awaitingToday = (! $hasQualifiedToday) && $hasQualifiedYesterday;

        if ($hasQualifiedToday) {
            $status = 'active_today';
        } elseif ($awaitingToday) {
            $status = 'awaiting_today';
        } else {
            $status = 'inactive';
        }

        // Current streak: count backwards from today if qualified, or from yesterday if awaiting_today
        $currentStreak = 0;
        if ($hasQualifiedToday) {
            $cursor = $todayLocal;
            while (isset($distinctDatesCounts[$cursor->format('Y-m-d')])) {
                $currentStreak++;
                $cursor = $cursor->subDays(1);
            }
        } elseif ($hasQualifiedYesterday) {
            $cursor = $todayLocal->subDays(1);
            while (isset($distinctDatesCounts[$cursor->format('Y-m-d')])) {
                $currentStreak++;
                $cursor = $cursor->subDays(1);
            }
        }

        // Longest streak: find maximum consecutive days across all history
        $longestStreak = 0;
        $totalDates = count($sortedAscDates);
        if ($totalDates > 0) {
            $longestStreak = 1;
            $currentRun = 1;
            for ($i = 1; $i < $totalDates; $i++) {
                $prevDate = CarbonImmutable::parse($sortedAscDates[$i - 1], $resolvedTz);
                $expectedNext = $prevDate->addDays(1)->format('Y-m-d');
                if ($sortedAscDates[$i] === $expectedNext) {
                    $currentRun++;
                    if ($currentRun > $longestStreak) {
                        $longestStreak = $currentRun;
                    }
                } else {
                    $currentRun = 1;
                }
            }
        }

        if ($currentStreak > $longestStreak) {
            $longestStreak = $currentStreak;
        }

        $totalQualifyingDays = $totalDates;
        $lastQualifiedDate = $totalDates > 0 ? $sortedAscDates[$totalDates - 1] : null;

        // Calendar days: continuous window of $resolvedDays up to local today
        $startDay = $todayLocal->subDays($resolvedDays - 1);
        $calendarDays = [];
        $cursor = $startDay;
        while ($cursor->lte($todayLocal)) {
            $dStr = $cursor->format('Y-m-d');
            $calendarDays[] = [
                'date' => $dStr,
                'day_of_week' => (int) $cursor->format('N'), // 1 (Mon) to 7 (Sun)
                'label' => $cursor->format('d/m'),
                'is_today' => $dStr === $todayStr,
                'qualified' => isset($distinctDatesCounts[$dStr]),
                'completed_sessions_count' => $distinctDatesCounts[$dStr] ?? 0,
            ];
            $cursor = $cursor->addDays(1);
        }

        return [
            'timezone' => $resolvedTz,
            'local_today' => $todayStr,
            'current_streak_days' => $currentStreak,
            'longest_streak_days' => $longestStreak,
            'total_qualifying_days' => $totalQualifyingDays,
            'last_qualified_date' => $lastQualifiedDate,
            'has_qualified_today' => $hasQualifiedToday,
            'awaiting_today' => $awaitingToday,
            'status' => $status,
            'calendar_days' => $calendarDays,
        ];
    }
}
