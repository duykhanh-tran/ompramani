<?php

declare(strict_types=1);

namespace App\Services\Practice;

use App\Enums\PracticeOccurrenceStatus;
use App\Enums\PracticePlanScheduleType;
use App\Enums\PracticePlanStatus;
use App\Models\PracticePlan;
use App\Models\PracticePlanItem;
use App\Models\PracticePlanOccurrenceOverride;
use App\Models\PracticePlanOccurrenceSession;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PracticePlanOccurrenceService
{
    /**
     * Maximum allowed range in days to prevent excessive computation.
     */
    public const MAX_RANGE_DAYS = 62;

    /**
     * Generate planned occurrences for a user within a bounded date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function generateOccurrences(
        User $user,
        CarbonInterface|string $from,
        CarbonInterface|string $to,
        ?int $planId = null
    ): array {
        $fromDate = $from instanceof CarbonInterface ? $from->copy()->startOfDay() : Carbon::parse($from)->startOfDay();
        $toDate = $to instanceof CarbonInterface ? $to->copy()->startOfDay() : Carbon::parse($to)->startOfDay();

        if ($fromDate->greaterThan($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $diffDays = $fromDate->diffInDays($toDate) + 1;
        if ($diffDays > self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'to' => ["Khoảng thời gian yêu cầu ({$diffDays} ngày) vượt quá giới hạn tối đa ".self::MAX_RANGE_DAYS.' ngày.'],
            ]);
        }

        // Fetch plans
        $plansQuery = PracticePlan::query()
            ->where('user_id', $user->id)
            ->with(['items' => function ($query): void {
                $query->orderBy('sort_order')->orderBy('id');
            }]);

        if ($planId !== null) {
            $plansQuery->where('id', $planId);
        }

        $plans = $plansQuery->get();
        if ($plans->isEmpty()) {
            return [];
        }

        $allItemIds = $plans->flatMap->items->pluck('id')->all();
        if (empty($allItemIds)) {
            return [];
        }

        $fromStr = $fromDate->format('Y-m-d');
        $toStr = $toDate->format('Y-m-d');

        // Bulk load overrides and linked sessions for these items in the date range
        $overrides = PracticePlanOccurrenceOverride::query()
            ->whereIn('practice_plan_item_id', $allItemIds)
            ->whereBetween('local_date', [$fromStr, $toStr])
            ->get()
            ->keyBy(fn (PracticePlanOccurrenceOverride $o): string => "{$o->practice_plan_item_id}_{$o->local_date->format('Y-m-d')}");

        $linkedSessions = PracticePlanOccurrenceSession::query()
            ->with('session')
            ->whereIn('practice_plan_item_id', $allItemIds)
            ->whereBetween('occurrence_local_date', [$fromStr, $toStr])
            ->get()
            ->keyBy(fn (PracticePlanOccurrenceSession $s): string => "{$s->practice_plan_item_id}_{$s->occurrence_local_date->format('Y-m-d')}");

        $occurrences = [];

        foreach ($plans as $plan) {
            $planTz = $plan->timezone ?: 'Asia/Ho_Chi_Minh';
            $todayInPlanTz = Carbon::now($planTz)->format('Y-m-d');
            $isPlanPaused = $plan->status === PracticePlanStatus::Paused;
            $isPlanArchived = $plan->status === PracticePlanStatus::Archived;

            $planStartsOn = $plan->starts_on?->format('Y-m-d');
            $planEndsOn = $plan->ends_on?->format('Y-m-d');

            /** @var PracticePlanItem $item */
            foreach ($plan->items as $item) {
                $itemStartsOn = $item->starts_on?->format('Y-m-d');
                $itemEndsOn = $item->ends_on?->format('Y-m-d');

                // Iterate each date in the requested range
                $cursor = $fromDate->copy();
                while ($cursor->lessThanOrEqualTo($toDate)) {
                    $dateStr = $cursor->format('Y-m-d');
                    $key = "{$item->id}_{$dateStr}";

                    /** @var PracticePlanOccurrenceSession|null $linked */
                    $linked = $linkedSessions->get($key);
                    /** @var PracticePlanOccurrenceOverride|null $override */
                    $override = $overrides->get($key);

                    // If plan is paused or archived, skip future occurrences unless an interaction exists
                    $isFuture = $dateStr >= $todayInPlanTz;
                    if (($isPlanPaused || $isPlanArchived) && $isFuture && ! $linked && ! $override) {
                        $cursor->addDay();

                        continue;
                    }

                    // If item is disabled, only show if historical interaction exists
                    if (! $item->is_enabled && ! $linked && ! $override) {
                        $cursor->addDay();

                        continue;
                    }

                    // Check boundaries unless historical interaction exists
                    if (! $linked) {
                        if ($planStartsOn && $dateStr < $planStartsOn) {
                            $cursor->addDay();

                            continue;
                        }
                        if ($planEndsOn && $dateStr > $planEndsOn) {
                            $cursor->addDay();

                            continue;
                        }
                        if ($itemStartsOn && $dateStr < $itemStartsOn) {
                            $cursor->addDay();

                            continue;
                        }
                        if ($itemEndsOn && $dateStr > $itemEndsOn) {
                            $cursor->addDay();

                            continue;
                        }
                    }

                    // Recurrence matching
                    $matches = false;
                    if ($item->schedule_type === PracticePlanScheduleType::Once) {
                        $matches = ($item->local_date && $item->local_date->format('Y-m-d') === $dateStr);
                    } elseif ($item->schedule_type === PracticePlanScheduleType::Weekly) {
                        $isoWeekday = $cursor->isoWeekday(); // 1 = Monday, 7 = Sunday
                        $weekdays = $item->weekdays ?? [];
                        $matches = in_array($isoWeekday, $weekdays, true);
                    }

                    // An occurrence exists if it matches the rule OR has a linked session
                    if (! $matches && ! $linked) {
                        $cursor->addDay();

                        continue;
                    }

                    // Determine status
                    if ($linked && $linked->session) {
                        $status = PracticeOccurrenceStatus::Practiced;
                    } elseif ($override && $override->action === 'skip') {
                        $status = PracticeOccurrenceStatus::Skipped;
                    } else {
                        $status = PracticeOccurrenceStatus::Planned;
                    }

                    // Actual data from authoritative completed PracticeSession
                    $actual = null;
                    if ($linked && $linked->session) {
                        $session = $linked->session;
                        $reps = null;
                        if (isset($session->progress_data['repetition_count'])) {
                            $reps = (int) $session->progress_data['repetition_count'];
                        } elseif (isset($session->progress_data['bead_count'])) {
                            $reps = (int) $session->progress_data['bead_count'];
                        }

                        $actual = [
                            'session_id' => $session->id,
                            'duration_seconds' => (int) $session->active_duration_seconds,
                            'repetition_count' => $reps,
                            'completed_at' => $session->ended_at?->toIso8601String(),
                            'status' => $session->status->value,
                            'status_label' => $session->status->label(),
                        ];
                    }

                    $occurrences[] = [
                        'occurrence_id' => "occ_{$item->id}_{$dateStr}",
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                        'plan_item_id' => $item->id,
                        'local_date' => $dateStr,
                        'local_time' => $item->local_time,
                        'timezone' => $planTz,
                        'practice_type' => $item->practice_type->value,
                        'practice_label' => $item->practice_type->label(),
                        'title' => $item->title ?: $item->practice_type->label(),
                        'schedule_type' => $item->schedule_type->value,
                        'schedule_revision' => $item->schedule_revision,
                        'target' => [
                            'duration_seconds' => $item->target_duration_seconds,
                            'repetitions' => $item->target_repetitions,
                        ],
                        'actual' => $actual,
                        'status' => $status->value,
                        'status_label' => $status->label(),
                        'linked_session_id' => $linked?->practice_session_id,
                        'destination_url' => $item->destinationUrl(),
                        'can_start' => $status === PracticeOccurrenceStatus::Planned,
                        'can_skip' => $status === PracticeOccurrenceStatus::Planned,
                        'can_restore' => $status === PracticeOccurrenceStatus::Skipped,
                        'sort_order' => $item->sort_order,
                    ];

                    $cursor->addDay();
                }
            }
        }

        // Sort occurrences by date ASC, local_time ASC NULLS LAST, sort_order ASC, plan_item_id ASC
        usort($occurrences, function (array $a, array $b): int {
            if ($a['local_date'] !== $b['local_date']) {
                return strcmp($a['local_date'], $b['local_date']);
            }

            $timeA = $a['local_time'] ?? '99:99';
            $timeB = $b['local_time'] ?? '99:99';
            if ($timeA !== $timeB) {
                return strcmp($timeA, $timeB);
            }

            if ($a['sort_order'] !== $b['sort_order']) {
                return $a['sort_order'] <=> $b['sort_order'];
            }

            return $a['plan_item_id'] <=> $b['plan_item_id'];
        });

        return $occurrences;
    }

    /**
     * Compute Monday-to-Sunday ISO week range for a given date in a given timezone.
     *
     * @return array{from: string, to: string, today: string}
     */
    public function getWeekRange(?string $date = null, string $timezone = 'Asia/Ho_Chi_Minh'): array
    {
        $target = $date ? Carbon::parse($date, $timezone) : Carbon::now($timezone);
        $today = Carbon::now($timezone)->format('Y-m-d');

        $startOfWeek = $target->copy()->startOfWeek(CarbonInterface::MONDAY);
        $endOfWeek = $target->copy()->endOfWeek(CarbonInterface::SUNDAY);

        return [
            'from' => $startOfWeek->format('Y-m-d'),
            'to' => $endOfWeek->format('Y-m-d'),
            'today' => $today,
        ];
    }
}
