<?php

declare(strict_types=1);

namespace App\Services\Prayer;

use App\Models\PrayerIntention;
use App\Models\PrayerParticipation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PrayerParticipationService
{
    /**
     * Express participation in a public prayer intention (idempotent Join).
     *
     * @return array{public_id: string, is_participating: bool, participation_count: int}
     */
    public function participate(User $user, string $publicId): array
    {
        $intention = PrayerIntention::where('public_id', $publicId)->first();

        // Must exist and be public approved
        if (! $intention || ! $intention->isPublicApproved()) {
            abort(404, 'Lời nguyện không tồn tại hoặc không còn được chia sẻ công khai.');
        }

        // Owner self-participation is forbidden by MVP policy
        if ($intention->user_id === $user->id) {
            abort(422, 'Bạn không thể tự đồng hành với lời nguyện của chính mình.');
        }

        return DB::transaction(function () use ($user, $intention, $publicId): array {
            // Re-verify under lock to prevent race conditions (e.g. concurrent owner withdrawal)
            /** @var PrayerIntention|null $lockedIntention */
            $lockedIntention = PrayerIntention::where('id', $intention->id)->lockForUpdate()->first();
            if (! $lockedIntention || ! $lockedIntention->isPublicApproved()) {
                abort(404, 'Lời nguyện không tồn tại hoặc không còn được chia sẻ công khai.');
            }

            /** @var PrayerParticipation|null $participation */
            $participation = PrayerParticipation::where('prayer_intention_id', $lockedIntention->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $participation) {
                PrayerParticipation::create([
                    'prayer_intention_id' => $lockedIntention->id,
                    'user_id' => $user->id,
                    'participated_at' => now(),
                    'withdrawn_at' => null,
                ]);
            } elseif ($participation->withdrawn_at !== null) {
                $participation->update([
                    'withdrawn_at' => null,
                    'participated_at' => now(),
                ]);
            }
            // If already active, it is idempotent: state remains active

            $count = PrayerParticipation::where('prayer_intention_id', $lockedIntention->id)
                ->whereNull('withdrawn_at')
                ->count();

            return [
                'public_id' => $publicId,
                'is_participating' => true,
                'participation_count' => $count,
            ];
        });
    }

    /**
     * Withdraw participation from a prayer intention (idempotent Withdraw).
     *
     * @return array{public_id: string, is_participating: bool, participation_count: int}
     */
    public function withdraw(User $user, string $publicId): array
    {
        $intention = PrayerIntention::where('public_id', $publicId)->first();

        if (! $intention) {
            abort(404, 'Lời nguyện không tồn tại.');
        }

        return DB::transaction(function () use ($user, $intention, $publicId): array {
            /** @var PrayerParticipation|null $participation */
            $participation = PrayerParticipation::where('prayer_intention_id', $intention->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($participation && $participation->withdrawn_at === null) {
                $participation->update([
                    'withdrawn_at' => now(),
                ]);
            }
            // If already withdrawn or never existed, it is idempotent: no-op

            $count = PrayerParticipation::where('prayer_intention_id', $intention->id)
                ->whereNull('withdrawn_at')
                ->count();

            return [
                'public_id' => $publicId,
                'is_participating' => false,
                'participation_count' => max(0, $count),
            ];
        });
    }

    /**
     * Batch lookup of viewer participation status for a list of public IDs.
     * Capped at 50 IDs per request to prevent abuse.
     *
     * @param  array<int, string>  $publicIds
     * @return array<string, bool>
     */
    public function getBatchStatus(User $user, array $publicIds): array
    {
        $cleanIds = array_slice(array_unique(array_filter($publicIds, 'is_string')), 0, 50);

        if (empty($cleanIds)) {
            return [];
        }

        // Only query public approved intentions to preserve privacy
        $intentions = PrayerIntention::publicApproved()
            ->whereIn('public_id', $cleanIds)
            ->pluck('id', 'public_id')
            ->all();

        if (empty($intentions)) {
            return array_fill_keys($cleanIds, false);
        }

        $internalIds = array_values($intentions);

        $activeParticipatedIntentionIds = PrayerParticipation::query()
            ->where('user_id', $user->id)
            ->whereIn('prayer_intention_id', $internalIds)
            ->whereNull('withdrawn_at')
            ->pluck('prayer_intention_id')
            ->all();

        $activeLookup = array_flip($activeParticipatedIntentionIds);

        $result = [];
        foreach ($cleanIds as $pid) {
            if (isset($intentions[$pid])) {
                $internalId = $intentions[$pid];
                $result[$pid] = isset($activeLookup[$internalId]);
            } else {
                $result[$pid] = false;
            }
        }

        return $result;
    }

    /**
     * Retrieve paginated personal participation history.
     * Strictly restricted to active participations for intentions that remain public and approved.
     */
    public function getHistoryForUser(User $user, int $perPage = 12): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 50);

        return PrayerParticipation::query()
            ->with(['prayerIntention' => function ($query): void {
                $query->publicApproved()->withCount('activeParticipations');
            }])
            ->where('user_id', $user->id)
            ->whereNull('withdrawn_at')
            ->whereHas('prayerIntention', function ($query): void {
                $query->publicApproved();
            })
            ->orderBy('participated_at', 'desc')
            ->paginate($perPage);
    }
}
