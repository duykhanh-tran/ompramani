<?php

declare(strict_types=1);

namespace App\Services\Practice;

use App\Enums\PracticePlanScheduleType;
use App\Enums\PracticeSessionStatus;
use App\Models\PracticePlanItem;
use App\Models\PracticePlanOccurrenceOverride;
use App\Models\PracticePlanOccurrenceSession;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PracticePlanSessionLinkService
{
    /**
     * Link an authoritative completed PracticeSession to a planned occurrence.
     */
    public function linkSession(
        User $user,
        int $planItemId,
        string $localDate,
        int $sessionId
    ): PracticePlanOccurrenceSession {
        /** @var PracticePlanItem $item */
        $item = PracticePlanItem::with('plan')->findOrFail($planItemId);

        if ($item->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'plan_item_id' => ['Bạn không có quyền thao tác trên mục kế hoạch này.'],
            ]);
        }

        $plan = $item->plan;
        $planTz = $plan->timezone ?: 'Asia/Ho_Chi_Minh';

        /** @var PracticeSession $session */
        $session = PracticeSession::findOrFail($sessionId);

        // 1. Session must belong to user
        if ($session->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'practice_session_id' => ['Buổi thực hành không thuộc quyền sở hữu của bạn.'],
            ]);
        }

        // 2. Session status must be Completed
        if ($session->status !== PracticeSessionStatus::Completed) {
            throw ValidationException::withMessages([
                'practice_session_id' => ['Chỉ có thể liên kết buổi thực hành đã hoàn thành.'],
            ]);
        }

        // 3. Practice type must match
        if ($session->practice_type !== $item->practice_type) {
            throw ValidationException::withMessages([
                'practice_session_id' => [
                    "Loại thực hành ({$session->practice_type->label()}) không khớp với kế hoạch ({$item->practice_type->label()}).",
                ],
            ]);
        }

        // 4. Session must not already be linked to another occurrence
        $alreadyLinked = PracticePlanOccurrenceSession::query()
            ->where('practice_session_id', $session->id)
            ->where(function ($query) use ($item, $localDate): void {
                $query->where('practice_plan_item_id', '!=', $item->id)
                    ->orWhere('occurrence_local_date', '!=', $localDate);
            })
            ->exists();

        if ($alreadyLinked) {
            throw ValidationException::withMessages([
                'practice_session_id' => ['Buổi thực hành này đã được liên kết với một hoạt động khác.'],
            ]);
        }

        // 5. Date matching policy: session completed date (or start date) in plan timezone must match localDate
        $sessionCompletedDate = $session->ended_at
            ? Carbon::instance($session->ended_at)->setTimezone($planTz)->format('Y-m-d')
            : null;

        $sessionStartedDate = $session->started_at
            ? Carbon::instance($session->started_at)->setTimezone($planTz)->format('Y-m-d')
            : null;

        if ($sessionCompletedDate !== $localDate && $sessionStartedDate !== $localDate) {
            throw ValidationException::withMessages([
                'practice_session_id' => [
                    "Ngày diễn ra buổi thực hành ({$sessionCompletedDate}) không khớp với ngày dự kiến ({$localDate}) theo múi giờ {$planTz}.",
                ],
            ]);
        }

        // 6. Occurrence must be a valid occurrence for this item
        $this->assertOccurrenceValid($item, $localDate);

        // 7. Remove skip override if present
        PracticePlanOccurrenceOverride::query()
            ->where('practice_plan_item_id', $item->id)
            ->where('local_date', $localDate)
            ->delete();

        // 8. Create or update link record
        return PracticePlanOccurrenceSession::updateOrCreate(
            [
                'practice_plan_item_id' => $item->id,
                'occurrence_local_date' => $localDate,
            ],
            [
                'user_id' => $user->id,
                'schedule_revision' => $item->schedule_revision,
                'practice_session_id' => $session->id,
                'linked_at' => now(),
            ]
        );
    }

    /**
     * Unlink a PracticeSession from an occurrence.
     * Note: Does NOT delete the underlying PracticeSession.
     */
    public function unlinkSession(User $user, int $planItemId, string $localDate): bool
    {
        /** @var PracticePlanItem $item */
        $item = PracticePlanItem::findOrFail($planItemId);

        if ($item->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'plan_item_id' => ['Bạn không có quyền thao tác trên mục kế hoạch này.'],
            ]);
        }

        return (bool) PracticePlanOccurrenceSession::query()
            ->where('practice_plan_item_id', $item->id)
            ->where('occurrence_local_date', $localDate)
            ->delete();
    }

    /**
     * Get candidate completed PracticeSessions that can be linked to this occurrence.
     *
     * @return Collection<int, PracticeSession>
     */
    public function getCandidateSessions(User $user, int $planItemId, string $localDate): Collection
    {
        /** @var PracticePlanItem $item */
        $item = PracticePlanItem::with('plan')->findOrFail($planItemId);

        if ($item->user_id !== $user->id) {
            return new Collection;
        }

        $planTz = $item->plan->timezone ?: 'Asia/Ho_Chi_Minh';

        // Range in UTC matching the local calendar day
        $dayStartUtc = Carbon::parse("{$localDate} 00:00:00", $planTz)->utc();
        $dayEndUtc = Carbon::parse("{$localDate} 23:59:59", $planTz)->utc();

        // Already linked session IDs
        $linkedSessionIds = PracticePlanOccurrenceSession::query()
            ->where('user_id', $user->id)
            ->pluck('practice_session_id');

        return PracticeSession::query()
            ->where('user_id', $user->id)
            ->where('practice_type', $item->practice_type)
            ->where('status', PracticeSessionStatus::Completed)
            ->whereNotIn('id', $linkedSessionIds)
            ->where(function ($query) use ($dayStartUtc, $dayEndUtc): void {
                $query->whereBetween('ended_at', [$dayStartUtc, $dayEndUtc])
                    ->orWhereBetween('started_at', [$dayStartUtc, $dayEndUtc]);
            })
            ->orderByDesc('ended_at')
            ->limit(10)
            ->get();
    }

    /**
     * Skip a planned occurrence.
     */
    public function skipOccurrence(
        User $user,
        int $planItemId,
        string $localDate,
        ?string $reason = null
    ): PracticePlanOccurrenceOverride {
        /** @var PracticePlanItem $item */
        $item = PracticePlanItem::findOrFail($planItemId);

        if ($item->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'plan_item_id' => ['Bạn không có quyền thao tác trên mục kế hoạch này.'],
            ]);
        }

        // Cannot skip if already linked to a session
        $hasLinkedSession = PracticePlanOccurrenceSession::query()
            ->where('practice_plan_item_id', $item->id)
            ->where('occurrence_local_date', $localDate)
            ->exists();

        if ($hasLinkedSession) {
            throw ValidationException::withMessages([
                'action' => ['Hoạt động này đã được ghi nhận thực hành, không thể bỏ qua. Vui lòng gỡ liên kết trước.'],
            ]);
        }

        $this->assertOccurrenceValid($item, $localDate);

        return PracticePlanOccurrenceOverride::firstOrCreate(
            [
                'practice_plan_item_id' => $item->id,
                'local_date' => $localDate,
                'action' => 'skip',
            ],
            [
                'user_id' => $user->id,
                'schedule_revision' => $item->schedule_revision,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Restore a skipped occurrence.
     */
    public function restoreOccurrence(User $user, int $planItemId, string $localDate): bool
    {
        /** @var PracticePlanItem $item */
        $item = PracticePlanItem::findOrFail($planItemId);

        if ($item->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'plan_item_id' => ['Bạn không có quyền thao tác trên mục kế hoạch này.'],
            ]);
        }

        return (bool) PracticePlanOccurrenceOverride::query()
            ->where('practice_plan_item_id', $item->id)
            ->where('local_date', $localDate)
            ->where('action', 'skip')
            ->delete();
    }

    /**
     * Verify that this localDate is a legitimate scheduled occurrence for the item.
     */
    private function assertOccurrenceValid(PracticePlanItem $item, string $localDate): void
    {
        $date = Carbon::parse($localDate);

        if ($item->schedule_type === PracticePlanScheduleType::Once) {
            if (! $item->local_date || $item->local_date->format('Y-m-d') !== $localDate) {
                throw ValidationException::withMessages([
                    'local_date' => ['Ngày được chọn không nằm trong lịch của hoạt động này.'],
                ]);
            }
        } elseif ($item->schedule_type === PracticePlanScheduleType::Weekly) {
            $isoWeekday = $date->isoWeekday();
            if (! in_array($isoWeekday, $item->weekdays ?? [], true)) {
                throw ValidationException::withMessages([
                    'local_date' => ['Thứ được chọn không nằm trong lịch lặp lại của hoạt động này.'],
                ]);
            }
        }
    }
}
