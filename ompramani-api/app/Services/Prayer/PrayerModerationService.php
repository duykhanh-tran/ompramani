<?php

declare(strict_types=1);

namespace App\Services\Prayer;

use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerRejectionReason;
use App\Enums\PrayerVisibility;
use App\Events\Prayer\PrayerIntentionApproved;
use App\Events\Prayer\PrayerIntentionRejected;
use App\Events\Prayer\PrayerIntentionRevoked;
use App\Models\PrayerIntention;
use App\Models\PrayerIntentionAudit;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PrayerModerationService
{
    /**
     * Approve a pending prayer intention for public community display.
     */
    public function approve(User $admin, PrayerIntention $intention, ?string $expectedUpdatedAt = null): PrayerIntention
    {
        return DB::transaction(function () use ($admin, $intention, $expectedUpdatedAt): PrayerIntention {
            // Reload with lock to avoid race conditions
            /** @var PrayerIntention $fresh */
            $fresh = PrayerIntention::where('id', $intention->id)->lockForUpdate()->firstOrFail();

            if ($fresh->trashed() || $fresh->withdrawn_at !== null) {
                throw new ConflictHttpException('Lời nguyện này đã bị rút lại hoặc xóa bởi người gửi.');
            }

            if ($fresh->moderation_status !== PrayerModerationStatus::Pending) {
                throw new ConflictHttpException('Lời nguyện này hiện không ở trạng thái chờ duyệt (trạng thái: '.$fresh->moderation_status->label().').');
            }

            if ($fresh->visibility !== PrayerVisibility::Public) {
                throw new ConflictHttpException('Lời nguyện này hiện ở chế độ riêng tư, không thể xuất bản.');
            }

            // Race check: If owner updated content since admin loaded the review screen
            if ($expectedUpdatedAt !== null && $fresh->updated_at->toIso8601String() !== $expectedUpdatedAt) {
                throw new ConflictHttpException('Nội dung lời nguyện đã được người gửi chỉnh sửa trong khi chờ duyệt. Vui lòng làm mới trang để xem phiên bản mới nhất trước khi duyệt.');
            }

            $oldStatus = $fresh->moderation_status;
            $now = now();

            $fresh->moderation_status = PrayerModerationStatus::Approved;
            $fresh->approved_at = $now;
            $fresh->published_at = $now;
            $fresh->withdrawn_at = null;
            $fresh->rejected_at = null;
            $fresh->moderated_by = $admin->id;
            $fresh->rejection_reason_code = null;
            $fresh->user_rejection_note = null;
            $fresh->save();

            $this->recordAudit(
                $fresh,
                $admin,
                'approved',
                $fresh->visibility->value,
                $fresh->visibility->value,
                $oldStatus->value,
                PrayerModerationStatus::Approved->value
            );

            Cache::forget('prayer_intentions_public_recent');

            PrayerIntentionApproved::dispatch($fresh, $admin);

            return $fresh;
        });
    }

    /**
     * Reject a pending prayer intention.
     */
    public function reject(
        User $admin,
        PrayerIntention $intention,
        string $reasonCode,
        ?string $userNote = null,
        ?string $adminNotes = null
    ): PrayerIntention {
        return DB::transaction(function () use ($admin, $intention, $reasonCode, $userNote, $adminNotes): PrayerIntention {
            /** @var PrayerIntention $fresh */
            $fresh = PrayerIntention::where('id', $intention->id)->lockForUpdate()->firstOrFail();

            if ($fresh->trashed() || $fresh->withdrawn_at !== null) {
                throw new ConflictHttpException('Lời nguyện này đã bị rút lại hoặc xóa bởi người gửi.');
            }

            if ($fresh->moderation_status !== PrayerModerationStatus::Pending) {
                throw new ConflictHttpException('Lời nguyện này không ở trạng thái chờ duyệt.');
            }

            $oldStatus = $fresh->moderation_status;
            $now = now();

            $reasonEnum = PrayerRejectionReason::tryFrom($reasonCode) ?? PrayerRejectionReason::Other;
            $finalUserNote = ! empty($userNote) ? trim($userNote) : $reasonEnum->userExplanation();

            $fresh->moderation_status = PrayerModerationStatus::Rejected;
            $fresh->rejected_at = $now;
            $fresh->moderated_by = $admin->id;
            $fresh->rejection_reason_code = $reasonEnum->value;
            $fresh->user_rejection_note = $finalUserNote;
            if ($adminNotes !== null) {
                $fresh->admin_notes = trim($adminNotes);
            }
            $fresh->save();

            $this->recordAudit(
                $fresh,
                $admin,
                'rejected',
                $fresh->visibility->value,
                $fresh->visibility->value,
                $oldStatus->value,
                PrayerModerationStatus::Rejected->value,
                $reasonEnum->value
            );

            PrayerIntentionRejected::dispatch($fresh, $admin, $reasonEnum->value, $finalUserNote);

            return $fresh;
        });
    }

    /**
     * Revoke / Withdraw a previously approved public prayer intention.
     */
    public function revoke(User $admin, PrayerIntention $intention, ?string $adminNotes = null): PrayerIntention
    {
        return DB::transaction(function () use ($admin, $intention, $adminNotes): PrayerIntention {
            /** @var PrayerIntention $fresh */
            $fresh = PrayerIntention::where('id', $intention->id)->lockForUpdate()->firstOrFail();

            $oldStatus = $fresh->moderation_status;
            $now = now();

            $fresh->moderation_status = PrayerModerationStatus::Rejected;
            $fresh->withdrawn_at = $now;
            $fresh->moderated_by = $admin->id;
            $fresh->rejection_reason_code = PrayerRejectionReason::Other->value;
            $fresh->user_rejection_note = 'Lời nguyện đã được rút khỏi không gian cộng đồng bởi ban quản trị.';
            if ($adminNotes !== null) {
                $fresh->admin_notes = trim($adminNotes);
            }
            $fresh->save();

            $this->recordAudit(
                $fresh,
                $admin,
                'revoked_by_admin',
                $fresh->visibility->value,
                $fresh->visibility->value,
                $oldStatus->value,
                PrayerModerationStatus::Rejected->value,
                PrayerRejectionReason::Other->value
            );

            Cache::forget('prayer_intentions_public_recent');

            PrayerIntentionRevoked::dispatch($fresh, $admin);

            return $fresh;
        });
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
