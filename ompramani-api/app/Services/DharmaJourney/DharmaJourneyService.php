<?php

namespace App\Services\DharmaJourney;

use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DharmaJourneyService
{
    /**
     * Allowed range keys.
     */
    public const ALLOWED_RANGES = ['7d', '30d', '90d', 'all'];

    /**
     * Fallback timezone identifier.
     */
    public const DEFAULT_TIMEZONE = 'Asia/Ho_Chi_Minh';

    /**
     * Resolve and validate timezone identifier.
     *
     * @throws ValidationException
     */
    public function resolveTimezone(?string $tz): string
    {
        if ($tz === null || trim($tz) === '') {
            return config('app.timezone', self::DEFAULT_TIMEZONE);
        }

        $trimmed = trim($tz);
        try {
            $tzObj = new DateTimeZone($trimmed);

            return $tzObj->getName();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'timezone' => "Múi giờ '{$tz}' không hợp lệ theo chuẩn IANA.",
            ]);
        }
    }

    /**
     * Resolve and validate time range key.
     *
     * @throws ValidationException
     */
    public function resolveRange(?string $range): string
    {
        if ($range === null || trim($range) === '') {
            return '30d';
        }

        $trimmed = trim($range);
        if (! in_array($trimmed, self::ALLOWED_RANGES, true)) {
            throw ValidationException::withMessages([
                'range' => "Khoảng thời gian '{$range}' không hợp lệ. Chỉ chấp nhận: 7d, 30d, 90d, all.",
            ]);
        }

        return $trimmed;
    }

    /**
     * Calculate start and end boundaries in local time and UTC.
     * Boundaries follow the half-open interval [startUtc, endUtc) where endUtc is next local midnight.
     *
     * @return array{
     *     start_local: ?CarbonImmutable,
     *     end_local: CarbonImmutable,
     *     start_utc: ?CarbonImmutable,
     *     end_utc: CarbonImmutable
     * }
     */
    public function calculateBoundaries(string $range, string $timezone): array
    {
        $nowLocal = CarbonImmutable::now($timezone);
        $todayStartLocal = $nowLocal->startOfDay();
        $endLocal = $todayStartLocal->addDay(); // Next local midnight (exclusive)
        $endUtc = $endLocal->setTimezone('UTC');

        if ($range === 'all') {
            return [
                'start_local' => null,
                'end_local' => $endLocal,
                'start_utc' => null,
                'end_utc' => $endUtc,
            ];
        }

        $days = match ($range) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30,
        };

        // start_local is (endLocal - $days) at 00:00:00 local time
        $startLocal = $endLocal->subDays($days);
        $startUtc = $startLocal->setTimezone('UTC');

        return [
            'start_local' => $startLocal,
            'end_local' => $endLocal,
            'start_utc' => $startUtc,
            'end_utc' => $endUtc,
        ];
    }

    /**
     * Get summary metrics for the given user in the specified range.
     *
     * @return array<string, mixed>
     */
    public function getSummary(User $user, string $range = '30d', ?string $timezone = null): array
    {
        $resolvedRange = $this->resolveRange($range);
        $resolvedTz = $this->resolveTimezone($timezone);
        $boundaries = $this->calculateBoundaries($resolvedRange, $resolvedTz);

        $baseQuery = $this->buildBaseQuery($user, $boundaries['start_utc'], $boundaries['end_utc']);

        // 1. Overall completed sessions count & total active duration (summed from backend active_duration_seconds)
        $overall = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_sessions, COALESCE(SUM(active_duration_seconds), 0) as total_duration')
            ->first();

        $completedSessionsCount = (int) ($overall->total_sessions ?? 0);
        $totalActiveDuration = (int) ($overall->total_duration ?? 0);

        // 2. Meditation metrics
        $meditationStats = (clone $baseQuery)
            ->where('practice_type', PracticeType::Meditation->value)
            ->selectRaw('COUNT(*) as sessions, COALESCE(SUM(active_duration_seconds), 0) as duration')
            ->first();

        // 3. Mala metrics (beads & completed rounds derived per completed session: sum(floor(bead_count / 108)))
        $malaRecords = (clone $baseQuery)
            ->where('practice_type', PracticeType::Mala->value)
            ->select(['progress_data'])
            ->get();

        $totalBeads = 0;
        $completedMalaRounds = 0;
        foreach ($malaRecords as $rec) {
            $data = $rec->progress_data;
            if (is_array($data) && isset($data['bead_count']) && is_numeric($data['bead_count'])) {
                $beads = (int) $data['bead_count'];
                if ($beads > 0) {
                    $totalBeads += $beads;
                    $completedMalaRounds += intdiv($beads, 108);
                }
            }
        }

        // 4. Mantra recitation metrics (manual repetitions only)
        $mantraRecords = (clone $baseQuery)
            ->where('practice_type', PracticeType::MantraRecitation->value)
            ->select(['progress_data'])
            ->get();

        $mantraRepetitions = 0;
        foreach ($mantraRecords as $rec) {
            $data = $rec->progress_data;
            if (is_array($data) && isset($data['repetition_count']) && is_numeric($data['repetition_count'])) {
                $reps = (int) $data['repetition_count'];
                if ($reps > 0) {
                    $mantraRepetitions += $reps;
                }
            }
        }

        // 5. Buddha name recitation metrics (manual repetitions only)
        $buddhaNameRecords = (clone $baseQuery)
            ->where('practice_type', PracticeType::BuddhaNameRecitation->value)
            ->select(['progress_data'])
            ->get();

        $buddhaNameRepetitions = 0;
        foreach ($buddhaNameRecords as $rec) {
            $data = $rec->progress_data;
            if (is_array($data) && isset($data['repetition_count']) && is_numeric($data['repetition_count'])) {
                $reps = (int) $data['repetition_count'];
                if ($reps > 0) {
                    $buddhaNameRepetitions += $reps;
                }
            }
        }

        // 6. Sutra completed practice sessions (sutra_reading, sutra_recitation)
        $sutraStats = (clone $baseQuery)
            ->whereIn('practice_type', [PracticeType::SutraReading->value, PracticeType::SutraRecitation->value])
            ->selectRaw('COUNT(*) as sessions, COALESCE(SUM(active_duration_seconds), 0) as duration')
            ->first();

        // 7. Bell and wooden fish metrics
        $bellRecords = (clone $baseQuery)
            ->where('practice_type', PracticeType::BellWoodenFish->value)
            ->select(['progress_data'])
            ->get();

        $bellCount = 0;
        $woodenFishCount = 0;
        foreach ($bellRecords as $rec) {
            $data = $rec->progress_data;
            if (is_array($data)) {
                if (isset($data['bell_count']) && is_numeric($data['bell_count'])) {
                    $bellCount += max(0, (int) $data['bell_count']);
                }
                if (isset($data['wooden_fish_count']) && is_numeric($data['wooden_fish_count'])) {
                    $woodenFishCount += max(0, (int) $data['wooden_fish_count']);
                }
            }
        }

        return [
            'range' => $resolvedRange,
            'timezone' => $resolvedTz,
            'date_from' => $boundaries['start_local']?->toIso8601String(),
            'date_to' => $boundaries['end_local']->subSecond()->toIso8601String(),
            'completed_sessions' => $completedSessionsCount,
            'total_active_duration_seconds' => $totalActiveDuration,
            'meditation' => [
                'sessions' => (int) ($meditationStats->sessions ?? 0),
                'active_duration_seconds' => (int) ($meditationStats->duration ?? 0),
            ],
            'mala' => [
                'sessions' => $malaRecords->count(),
                'bead_count' => $totalBeads,
                'completed_rounds' => $completedMalaRounds,
            ],
            'mantra' => [
                'sessions' => $mantraRecords->count(),
                'repetition_count' => $mantraRepetitions,
            ],
            'buddha_name' => [
                'sessions' => $buddhaNameRecords->count(),
                'repetition_count' => $buddhaNameRepetitions,
            ],
            'sutra' => [
                'sessions' => (int) ($sutraStats->sessions ?? 0),
                'active_duration_seconds' => (int) ($sutraStats->duration ?? 0),
            ],
            'bell_wooden_fish' => [
                'sessions' => $bellRecords->count(),
                'bell_count' => $bellCount,
                'wooden_fish_count' => $woodenFishCount,
            ],
        ];
    }

    /**
     * Get daily activity chart points for the user with zero-filled continuity.
     *
     * @return array<string, mixed>
     */
    public function getActivityChart(User $user, string $range = '30d', ?string $timezone = null): array
    {
        $resolvedRange = $this->resolveRange($range);
        $resolvedTz = $this->resolveTimezone($timezone);
        $boundaries = $this->calculateBoundaries($resolvedRange, $resolvedTz);

        $baseQuery = $this->buildBaseQuery($user, $boundaries['start_utc'], $boundaries['end_utc']);

        // Retrieve completed sessions in range with only ended_at and active_duration_seconds
        $sessions = (clone $baseQuery)
            ->select(['ended_at', 'active_duration_seconds'])
            ->get();

        if ($resolvedRange === 'all') {
            return $this->buildMonthlyActivityPoints($sessions, $resolvedTz, $boundaries['end_local']);
        }

        return $this->buildDailyActivityPoints(
            $sessions,
            $resolvedTz,
            $boundaries['start_local'],
            $boundaries['end_local'],
            $resolvedRange
        );
    }

    /**
     * Get practice type breakdown for the user.
     *
     * @return array<string, mixed>
     */
    public function getBreakdown(User $user, string $range = '30d', ?string $timezone = null): array
    {
        $resolvedRange = $this->resolveRange($range);
        $resolvedTz = $this->resolveTimezone($timezone);
        $boundaries = $this->calculateBoundaries($resolvedRange, $resolvedTz);

        $baseQuery = $this->buildBaseQuery($user, $boundaries['start_utc'], $boundaries['end_utc']);

        // Group by practice_type
        $byType = (clone $baseQuery)
            ->selectRaw('practice_type, COUNT(*) as sessions_count, COALESCE(SUM(active_duration_seconds), 0) as duration_seconds')
            ->groupBy('practice_type')
            ->get()
            ->keyBy(function ($row) {
                return $row->practice_type instanceof PracticeType
                    ? $row->practice_type->value
                    : (string) $row->practice_type;
            });

        // Pull progress data for types that have specific metrics
        $records = (clone $baseQuery)
            ->whereIn('practice_type', [
                PracticeType::Mala->value,
                PracticeType::MantraRecitation->value,
                PracticeType::BuddhaNameRecitation->value,
                PracticeType::BellWoodenFish->value,
            ])
            ->select(['practice_type', 'progress_data'])
            ->get();

        // Calculate specific metrics per type
        $malaBeads = 0;
        $malaRounds = 0;
        $mantraReps = 0;
        $buddhaNameReps = 0;
        $bellCount = 0;
        $woodenFishCount = 0;

        foreach ($records as $rec) {
            $pType = $rec->practice_type instanceof PracticeType ? $rec->practice_type->value : (string) $rec->practice_type;
            $data = is_array($rec->progress_data) ? $rec->progress_data : [];

            if ($pType === PracticeType::Mala->value) {
                $beads = isset($data['bead_count']) && is_numeric($data['bead_count']) ? (int) $data['bead_count'] : 0;
                if ($beads > 0) {
                    $malaBeads += $beads;
                    $malaRounds += intdiv($beads, 108);
                }
            } elseif ($pType === PracticeType::MantraRecitation->value) {
                $reps = isset($data['repetition_count']) && is_numeric($data['repetition_count']) ? (int) $data['repetition_count'] : 0;
                if ($reps > 0) {
                    $mantraReps += $reps;
                }
            } elseif ($pType === PracticeType::BuddhaNameRecitation->value) {
                $reps = isset($data['repetition_count']) && is_numeric($data['repetition_count']) ? (int) $data['repetition_count'] : 0;
                if ($reps > 0) {
                    $buddhaNameReps += $reps;
                }
            } elseif ($pType === PracticeType::BellWoodenFish->value) {
                if (isset($data['bell_count']) && is_numeric($data['bell_count'])) {
                    $bellCount += max(0, (int) $data['bell_count']);
                }
                if (isset($data['wooden_fish_count']) && is_numeric($data['wooden_fish_count'])) {
                    $woodenFishCount += max(0, (int) $data['wooden_fish_count']);
                }
            }
        }

        $items = [];
        foreach (PracticeType::cases() as $typeCase) {
            $typeVal = $typeCase->value;
            $row = $byType->get($typeVal);

            $sessions = (int) ($row->sessions_count ?? 0);
            $duration = (int) ($row->duration_seconds ?? 0);

            $metrics = match ($typeCase) {
                PracticeType::Mala => [
                    'bead_count' => $malaBeads,
                    'completed_rounds' => $malaRounds,
                ],
                PracticeType::MantraRecitation => [
                    'repetition_count' => $mantraReps,
                ],
                PracticeType::BuddhaNameRecitation => [
                    'repetition_count' => $buddhaNameReps,
                ],
                PracticeType::BellWoodenFish => [
                    'bell_count' => $bellCount,
                    'wooden_fish_count' => $woodenFishCount,
                ],
                default => (object) [],
            };

            $items[] = [
                'practice_type' => $typeVal,
                'label' => $typeCase->label(),
                'completed_sessions' => $sessions,
                'active_duration_seconds' => $duration,
                'metrics' => $metrics,
            ];
        }

        return [
            'range' => $resolvedRange,
            'timezone' => $resolvedTz,
            'items' => $items,
        ];
    }

    /**
     * Get complete dashboard payload (summary + activity + breakdown) in a single response.
     *
     * @return array<string, mixed>
     */
    public function getDashboard(User $user, string $range = '30d', ?string $timezone = null): array
    {
        return [
            'summary' => $this->getSummary($user, $range, $timezone),
            'activity' => $this->getActivityChart($user, $range, $timezone),
            'breakdown' => $this->getBreakdown($user, $range, $timezone),
        ];
    }

    /**
     * Build base query for user's completed sessions within boundaries.
     */
    protected function buildBaseQuery(User $user, ?CarbonImmutable $startUtc, CarbonImmutable $endUtc)
    {
        $query = PracticeSession::query()
            ->where('user_id', $user->id)
            ->where('status', PracticeSessionStatus::Completed->value);

        if ($startUtc !== null) {
            $query->where('ended_at', '>=', $startUtc);
        }

        $query->where('ended_at', '<', $endUtc);

        return $query;
    }

    /**
     * Build continuous daily points for the given range.
     *
     * @param  Collection<int, PracticeSession>  $sessions
     * @return array<string, mixed>
     */
    protected function buildDailyActivityPoints(
        $sessions,
        string $tz,
        CarbonImmutable $startLocal,
        CarbonImmutable $endLocal,
        string $range
    ): array {
        $buckets = [];
        foreach ($sessions as $session) {
            if (! $session->ended_at) {
                continue;
            }
            $dateStr = $session->ended_at->setTimezone($tz)->format('Y-m-d');
            if (! isset($buckets[$dateStr])) {
                $buckets[$dateStr] = [
                    'completed_sessions' => 0,
                    'active_duration_seconds' => 0,
                ];
            }
            $buckets[$dateStr]['completed_sessions']++;
            $buckets[$dateStr]['active_duration_seconds'] += (int) $session->active_duration_seconds;
        }

        $points = [];
        $current = $startLocal;
        while ($current->lt($endLocal)) {
            $key = $current->format('Y-m-d');
            $points[] = [
                'date' => $key,
                'label' => $current->format('d/m'),
                'completed_sessions' => $buckets[$key]['completed_sessions'] ?? 0,
                'active_duration_seconds' => $buckets[$key]['active_duration_seconds'] ?? 0,
            ];
            $current = $current->addDay();
        }

        return [
            'range' => $range,
            'timezone' => $tz,
            'granularity' => 'day',
            'points' => $points,
        ];
    }

    /**
     * Build continuous monthly points for 'all' range.
     *
     * @param  Collection<int, PracticeSession>  $sessions
     * @return array<string, mixed>
     */
    protected function buildMonthlyActivityPoints($sessions, string $tz, CarbonImmutable $endLocal): array
    {
        $buckets = [];
        $earliestLocal = $endLocal->subMonths(6)->startOfMonth();

        foreach ($sessions as $session) {
            if (! $session->ended_at) {
                continue;
            }
            $sessionLocal = $session->ended_at->setTimezone($tz);
            $monthKey = $sessionLocal->format('Y-m');

            if (! isset($buckets[$monthKey])) {
                $buckets[$monthKey] = [
                    'completed_sessions' => 0,
                    'active_duration_seconds' => 0,
                ];
            }
            $buckets[$monthKey]['completed_sessions']++;
            $buckets[$monthKey]['active_duration_seconds'] += (int) $session->active_duration_seconds;

            if ($sessionLocal->startOfMonth()->lt($earliestLocal)) {
                $earliestLocal = $sessionLocal->startOfMonth();
            }
        }

        $currentMonth = $earliestLocal;
        $targetMonth = $endLocal->startOfMonth();
        $points = [];

        while ($currentMonth->lte($targetMonth)) {
            $key = $currentMonth->format('Y-m');
            $points[] = [
                'date' => $key,
                'label' => 'Th '.$currentMonth->format('m/Y'),
                'completed_sessions' => $buckets[$key]['completed_sessions'] ?? 0,
                'active_duration_seconds' => $buckets[$key]['active_duration_seconds'] ?? 0,
            ];
            $currentMonth = $currentMonth->addMonth();
        }

        return [
            'range' => 'all',
            'timezone' => $tz,
            'granularity' => 'month',
            'points' => $points,
        ];
    }
}
