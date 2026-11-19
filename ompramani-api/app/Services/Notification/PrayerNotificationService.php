<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\PrayerNotificationType;
use App\Models\PrayerIntention;
use App\Models\PrayerParticipation;
use App\Models\PrayerResponse;
use App\Models\User;
use App\Notifications\PrayerCommunityNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class PrayerNotificationService
{
    /**
     * Notify owner that their prayer intention was approved.
     */
    public function notifyPrayerIntentionApproved(PrayerIntention $intention): bool
    {
        $owner = $intention->user;
        if (! $owner) {
            return false;
        }

        $preferences = $owner->getEffectiveNotificationPreferences();
        if (! $preferences->moderation_updates_enabled) {
            return false;
        }

        $timestamp = $intention->published_at ? $intention->published_at->timestamp : $intention->updated_at->timestamp;
        $idempotencyKey = "prayer_intention_approved:{$intention->id}:{$timestamp}";

        if ($this->hasNotificationWithKey($owner, $idempotencyKey)) {
            return false;
        }

        $owner->notify(new PrayerCommunityNotification(
            PrayerNotificationType::PrayerIntentionApproved,
            'prayer_intention',
            $intention->public_id,
            [
                'idempotency_key' => $idempotencyKey,
                'resource_public_id' => $intention->public_id,
            ]
        ));

        return true;
    }

    /**
     * Notify owner that their prayer intention was rejected.
     */
    public function notifyPrayerIntentionRejected(PrayerIntention $intention, ?string $userNote = null): bool
    {
        $owner = $intention->user;
        if (! $owner) {
            return false;
        }

        $preferences = $owner->getEffectiveNotificationPreferences();
        if (! $preferences->moderation_updates_enabled) {
            return false;
        }

        $timestamp = $intention->rejected_at ? $intention->rejected_at->timestamp : $intention->updated_at->timestamp;
        $idempotencyKey = "prayer_intention_rejected:{$intention->id}:{$timestamp}";

        if ($this->hasNotificationWithKey($owner, $idempotencyKey)) {
            return false;
        }

        $owner->notify(new PrayerCommunityNotification(
            PrayerNotificationType::PrayerIntentionRejected,
            'prayer_intention',
            $intention->public_id,
            [
                'idempotency_key' => $idempotencyKey,
                'resource_public_id' => $intention->public_id,
            ]
        ));

        return true;
    }

    /**
     * Notify owner that their prayer intention was revoked.
     */
    public function notifyPrayerIntentionRevoked(PrayerIntention $intention): bool
    {
        $owner = $intention->user;
        if (! $owner) {
            return false;
        }

        $preferences = $owner->getEffectiveNotificationPreferences();
        if (! $preferences->moderation_updates_enabled) {
            return false;
        }

        $timestamp = $intention->withdrawn_at ? $intention->withdrawn_at->timestamp : $intention->updated_at->timestamp;
        $idempotencyKey = "prayer_intention_revoked:{$intention->id}:{$timestamp}";

        if ($this->hasNotificationWithKey($owner, $idempotencyKey)) {
            return false;
        }

        $owner->notify(new PrayerCommunityNotification(
            PrayerNotificationType::PrayerIntentionRevoked,
            'prayer_intention',
            $intention->public_id,
            [
                'idempotency_key' => $idempotencyKey,
                'resource_public_id' => $intention->public_id,
            ]
        ));

        return true;
    }

    /**
     * Notify response author and (optionally) parent owner that a response was approved.
     */
    public function notifyPrayerResponseApproved(PrayerResponse $response): void
    {
        // 1. Notify Response Author
        $author = $response->user;
        if ($author) {
            $preferences = $author->getEffectiveNotificationPreferences();
            if ($preferences->moderation_updates_enabled) {
                $idempotencyKey = "prayer_response_approved:{$response->id}:{$response->submission_revision}";
                if (! $this->hasNotificationWithKey($author, $idempotencyKey)) {
                    $author->notify(new PrayerCommunityNotification(
                        PrayerNotificationType::PrayerResponseApproved,
                        'prayer_response',
                        $response->public_id,
                        [
                            'idempotency_key' => $idempotencyKey,
                            'resource_public_id' => $response->public_id,
                        ]
                    ));
                }
            }
        }

        // 2. Notify Parent Prayer Owner if opted in and not the same user
        $parent = $response->prayerIntention;
        if (! $parent || ! $parent->isPublicApproved()) {
            return;
        }

        $parentOwner = $parent->user;
        if (! $parentOwner || $parentOwner->id === $response->user_id) {
            // Deduplicate: If owner wrote response under own prayer, do not send "new response" notice
            return;
        }

        $parentPreferences = $parentOwner->getEffectiveNotificationPreferences();
        if (! $parentPreferences->new_response_updates_enabled) {
            return;
        }

        $parentNoticeKey = "prayer_new_approved_response:{$parent->id}:{$response->id}:{$response->submission_revision}";
        if (! $this->hasNotificationWithKey($parentOwner, $parentNoticeKey)) {
            $parentOwner->notify(new PrayerCommunityNotification(
                PrayerNotificationType::PrayerNewApprovedResponse,
                'prayer_intention',
                $parent->public_id,
                [
                    'idempotency_key' => $parentNoticeKey,
                    'resource_public_id' => $parent->public_id,
                    'parent_public_id' => $parent->public_id,
                ]
            ));
        }
    }

    /**
     * Notify response author that their response was rejected.
     */
    public function notifyPrayerResponseRejected(PrayerResponse $response, ?string $userNote = null): bool
    {
        $author = $response->user;
        if (! $author) {
            return false;
        }

        $preferences = $author->getEffectiveNotificationPreferences();
        if (! $preferences->moderation_updates_enabled) {
            return false;
        }

        $timestamp = $response->rejected_at ? $response->rejected_at->timestamp : $response->updated_at->timestamp;
        $idempotencyKey = "prayer_response_rejected:{$response->id}:{$timestamp}";

        if ($this->hasNotificationWithKey($author, $idempotencyKey)) {
            return false;
        }

        $author->notify(new PrayerCommunityNotification(
            PrayerNotificationType::PrayerResponseRejected,
            'prayer_response',
            $response->public_id,
            [
                'idempotency_key' => $idempotencyKey,
                'resource_public_id' => $response->public_id,
            ]
        ));

        return true;
    }

    /**
     * Notify response author that their response was revoked.
     */
    public function notifyPrayerResponseRevoked(PrayerResponse $response): bool
    {
        $author = $response->user;
        if (! $author) {
            return false;
        }

        $preferences = $author->getEffectiveNotificationPreferences();
        if (! $preferences->moderation_updates_enabled) {
            return false;
        }

        $timestamp = $response->revoked_at ? $response->revoked_at->timestamp : $response->updated_at->timestamp;
        $idempotencyKey = "prayer_response_revoked:{$response->id}:{$timestamp}";

        if ($this->hasNotificationWithKey($author, $idempotencyKey)) {
            return false;
        }

        $author->notify(new PrayerCommunityNotification(
            PrayerNotificationType::PrayerResponseRevoked,
            'prayer_response',
            $response->public_id,
            [
                'idempotency_key' => $idempotencyKey,
                'resource_public_id' => $response->public_id,
            ]
        ));

        return true;
    }

    /**
     * Process daily participation digests for opted-in users.
     */
    public function sendDailyParticipationDigests(?CarbonInterface $targetDate = null): int
    {
        $date = $targetDate ? Carbon::instance($targetDate) : now()->subDay();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        $dateStr = $date->format('Y-m-d');

        // Only process users who have explicitly enabled participation digests
        $optedInUsers = User::whereHas('notificationPreferences', function ($q) {
            $q->where('participation_digest_enabled', true);
        })->get();

        $deliveredCount = 0;

        foreach ($optedInUsers as $user) {
            $idempotencyKey = "prayer_participation_digest:{$user->id}:{$dateStr}";

            if ($this->hasNotificationWithKey($user, $idempotencyKey)) {
                continue;
            }

            // Get user's currently public and approved prayer intention IDs
            $publicPrayerIds = PrayerIntention::where('user_id', $user->id)
                ->publicApproved()
                ->pluck('id');

            if ($publicPrayerIds->isEmpty()) {
                continue;
            }

            // Count distinct active participants who joined during the target period
            $distinctParticipants = PrayerParticipation::whereIn('prayer_intention_id', $publicPrayerIds)
                ->active()
                ->whereBetween('participated_at', [$startOfDay, $endOfDay])
                ->distinct('user_id')
                ->count('user_id');

            if ($distinctParticipants <= 0) {
                continue;
            }

            $user->notify(new PrayerCommunityNotification(
                PrayerNotificationType::PrayerParticipationDigest,
                'prayer_intention',
                'digest',
                [
                    'idempotency_key' => $idempotencyKey,
                    'count' => $distinctParticipants,
                    'date' => $dateStr,
                ]
            ));

            $deliveredCount++;
        }

        return $deliveredCount;
    }

    /**
     * Check if a user already received a notification with a specific idempotency key.
     */
    protected function hasNotificationWithKey(User $user, string $key): bool
    {
        return $user->notifications()
            ->where('data', 'like', '%"idempotency_key":"'.$key.'"%')
            ->exists();
    }
}
