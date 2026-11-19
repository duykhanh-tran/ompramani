<?php

declare(strict_types=1);

namespace App\Services\Prayer;

use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerVisibility;
use App\Models\PrayerIntention;
use App\Models\PrayerIntentionAudit;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrayerIntentionService
{
    public const PUBLIC_FEED_CACHE_TAG = 'prayer_public_feed';

    /**
     * Create a new intention as Private (Default).
     */
    public function createPrivate(User $user, array $data): PrayerIntention
    {
        return DB::transaction(function () use ($user, $data): PrayerIntention {
            $intention = PrayerIntention::create([
                'user_id' => $user->id,
                'title' => ! empty($data['title']) ? trim((string) $data['title']) : null,
                'body' => trim((string) $data['body']),
                'category' => ! empty($data['category']) ? $data['category'] : null,
                'visibility' => PrayerVisibility::Private,
                'moderation_status' => PrayerModerationStatus::NotRequired,
            ]);

            $this->recordAudit($intention, $user, 'created_private', null, PrayerVisibility::Private->value, null, PrayerModerationStatus::NotRequired->value);

            return $intention;
        });
    }

    /**
     * Create a new intention with request to Share with Community (Pending Moderation).
     */
    public function createPublic(User $user, array $data, bool $consentGiven): PrayerIntention
    {
        if (! $consentGiven) {
            throw ValidationException::withMessages([
                'consent_public_sharing' => ['Bạn cần xác nhận đồng ý với hướng dẫn quyền riêng tư trước khi gửi chia sẻ cộng đồng.'],
            ]);
        }

        return DB::transaction(function () use ($user, $data): PrayerIntention {
            $now = now();
            $intention = PrayerIntention::create([
                'user_id' => $user->id,
                'title' => ! empty($data['title']) ? trim((string) $data['title']) : null,
                'body' => trim((string) $data['body']),
                'category' => ! empty($data['category']) ? $data['category'] : null,
                'visibility' => PrayerVisibility::Public,
                'moderation_status' => PrayerModerationStatus::Pending,
                'submitted_at' => $now,
            ]);

            $this->recordAudit($intention, $user, 'submitted_for_review', null, PrayerVisibility::Public->value, null, PrayerModerationStatus::Pending->value);

            return $intention;
        });
    }

    /**
     * Update an existing intention with strict security invariants for published content.
     */
    public function update(
        User $user,
        PrayerIntention $intention,
        array $data,
        ?bool $requestPublic = null,
        bool $consentGiven = false
    ): PrayerIntention {
        return DB::transaction(function () use ($user, $intention, $data, $requestPublic, $consentGiven): PrayerIntention {
            $oldVisibility = $intention->visibility;
            $oldStatus = $intention->moderation_status;
            $now = now();

            $newTitle = array_key_exists('title', $data)
                ? (! empty($data['title']) ? trim((string) $data['title']) : null)
                : $intention->title;

            $newBody = array_key_exists('body', $data)
                ? trim((string) $data['body'])
                : $intention->body;

            $newCategory = array_key_exists('category', $data)
                ? (! empty($data['category']) ? $data['category'] : null)
                : $intention->category;

            $intention->title = $newTitle;
            $intention->body = $newBody;
            $intention->category = $newCategory;

            // Handle security invariant: If already approved & published
            if ($oldStatus === PrayerModerationStatus::Approved) {
                // Changing published content immediately withdraws approval
                if ($requestPublic === true || ($requestPublic === null && $oldVisibility === PrayerVisibility::Public)) {
                    if (! $consentGiven) {
                        throw ValidationException::withMessages([
                            'consent_public_sharing' => ['Khi cập nhật nội dung đã đăng, bạn cần xác nhận để gửi kiểm duyệt lại trước khi xuất bản bản mới.'],
                        ]);
                    }
                    $intention->visibility = PrayerVisibility::Public;
                    $intention->moderation_status = PrayerModerationStatus::Pending;
                    $intention->submitted_at = $now;
                    $intention->approved_at = null;
                    $intention->published_at = null;
                    $intention->withdrawn_at = null;
                    $action = 'resubmitted_after_edit';
                } else {
                    $intention->visibility = PrayerVisibility::Private;
                    $intention->moderation_status = PrayerModerationStatus::NotRequired;
                    $intention->approved_at = null;
                    $intention->published_at = null;
                    $intention->withdrawn_at = $now;
                    $action = 'withdrawn_to_private';
                }
                $this->invalidatePublicCache();
            } elseif ($oldStatus === PrayerModerationStatus::Pending) {
                // Currently pending review
                if ($requestPublic === false) {
                    $intention->visibility = PrayerVisibility::Private;
                    $intention->moderation_status = PrayerModerationStatus::NotRequired;
                    $action = 'withdrawn_to_private';
                } else {
                    $intention->submitted_at = $now;
                    $action = 'updated_pending';
                }
            } elseif ($oldStatus === PrayerModerationStatus::Rejected) {
                // Previously rejected - user edits and resubmits
                if ($requestPublic === true) {
                    if (! $consentGiven) {
                        throw ValidationException::withMessages([
                            'consent_public_sharing' => ['Bạn cần xác nhận đồng ý với hướng dẫn quyền riêng tư trước khi gửi duyệt lại.'],
                        ]);
                    }
                    $intention->visibility = PrayerVisibility::Public;
                    $intention->moderation_status = PrayerModerationStatus::Pending;
                    $intention->submitted_at = $now;
                    $intention->rejected_at = null;
                    $intention->rejection_reason_code = null;
                    $intention->user_rejection_note = null;
                    $action = 'resubmitted';
                } else {
                    $action = 'updated_rejected';
                }
            } else {
                // Private intention
                if ($requestPublic === true) {
                    if (! $consentGiven) {
                        throw ValidationException::withMessages([
                            'consent_public_sharing' => ['Bạn cần xác nhận đồng ý với hướng dẫn quyền riêng tư trước khi gửi chia sẻ cộng đồng.'],
                        ]);
                    }
                    $intention->visibility = PrayerVisibility::Public;
                    $intention->moderation_status = PrayerModerationStatus::Pending;
                    $intention->submitted_at = $now;
                    $action = 'submitted_for_review';
                } else {
                    $action = 'updated_private';
                }
            }

            $intention->save();

            $this->recordAudit(
                $intention,
                $user,
                $action,
                $oldVisibility->value,
                $intention->visibility->value,
                $oldStatus->value,
                $intention->moderation_status->value
            );

            return $intention;
        });
    }

    /**
     * Submit an existing private or rejected intention for public moderation.
     */
    public function submitForReview(User $user, PrayerIntention $intention, bool $consentGiven): PrayerIntention
    {
        if (! $consentGiven) {
            throw ValidationException::withMessages([
                'consent_public_sharing' => ['Bạn cần xác nhận đồng ý với hướng dẫn quyền riêng tư trước khi gửi chia sẻ cộng đồng.'],
            ]);
        }

        if ($intention->moderation_status === PrayerModerationStatus::Approved && $intention->withdrawn_at === null) {
            throw ValidationException::withMessages([
                'status' => ['Lời nguyện này đã được công khai trong cộng đồng.'],
            ]);
        }

        return DB::transaction(function () use ($user, $intention): PrayerIntention {
            $oldVisibility = $intention->visibility;
            $oldStatus = $intention->moderation_status;
            $now = now();

            $intention->visibility = PrayerVisibility::Public;
            $intention->moderation_status = PrayerModerationStatus::Pending;
            $intention->submitted_at = $now;
            $intention->withdrawn_at = null;
            $intention->approved_at = null;
            $intention->published_at = null;
            $intention->rejected_at = null;
            $intention->rejection_reason_code = null;
            $intention->user_rejection_note = null;
            $intention->save();

            $action = $oldStatus === PrayerModerationStatus::Rejected ? 'resubmitted' : 'submitted_for_review';

            $this->recordAudit(
                $intention,
                $user,
                $action,
                $oldVisibility->value,
                PrayerVisibility::Public->value,
                $oldStatus->value,
                PrayerModerationStatus::Pending->value
            );

            return $intention;
        });
    }

    /**
     * Stop sharing / withdraw intention immediately.
     */
    public function withdraw(User $user, PrayerIntention $intention): PrayerIntention
    {
        return DB::transaction(function () use ($user, $intention): PrayerIntention {
            $oldVisibility = $intention->visibility;
            $oldStatus = $intention->moderation_status;
            $now = now();

            $intention->visibility = PrayerVisibility::Private;
            $intention->moderation_status = PrayerModerationStatus::NotRequired;
            $intention->withdrawn_at = $now;
            $intention->save();

            $this->recordAudit(
                $intention,
                $user,
                'withdrawn_by_owner',
                $oldVisibility->value,
                PrayerVisibility::Private->value,
                $oldStatus->value,
                PrayerModerationStatus::NotRequired->value
            );

            $this->invalidatePublicCache();

            return $intention;
        });
    }

    /**
     * Soft delete owner's intention.
     */
    public function delete(User $user, PrayerIntention $intention): bool
    {
        return DB::transaction(function () use ($user, $intention): bool {
            $oldVisibility = $intention->visibility;
            $oldStatus = $intention->moderation_status;

            if ($intention->visibility === PrayerVisibility::Public) {
                $intention->withdrawn_at = now();
                $intention->save();
                $this->invalidatePublicCache();
            }

            $this->recordAudit(
                $intention,
                $user,
                'deleted',
                $oldVisibility->value,
                null,
                $oldStatus->value,
                null
            );

            return (bool) $intention->delete();
        });
    }

    public function invalidatePublicCache(): void
    {
        Cache::forget('prayer_intentions_public_recent');
    }

    protected function recordAudit(
        PrayerIntention $intention,
        User $actor,
        string $action,
        ?string $fromVisibility,
        ?string $toVisibility,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $reasonCode = null,
        ?array $metadata = null
    ): void {
        PrayerIntentionAudit::create([
            'prayer_intention_id' => $intention->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'from_visibility' => $fromVisibility,
            'to_visibility' => $toVisibility,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason_code' => $reasonCode,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
