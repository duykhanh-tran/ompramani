<?php

declare(strict_types=1);

namespace App\Services\Prayer;

use App\Enums\PrayerResponseModerationStatus;
use App\Enums\PrayerResponseReportStatus;
use App\Events\Prayer\PrayerResponseApproved;
use App\Events\Prayer\PrayerResponseRejected;
use App\Events\Prayer\PrayerResponseRevoked;
use App\Models\PrayerResponse;
use App\Models\PrayerResponseAudit;
use App\Models\PrayerResponseReport;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PrayerResponseModerationService
{
    /**
     * Approve a pending prayer response.
     */
    public function approve(User $admin, PrayerResponse $response, ?int $expectedRevision = null): PrayerResponse
    {
        return DB::transaction(function () use ($admin, $response, $expectedRevision): PrayerResponse {
            /** @var PrayerResponse $fresh */
            $fresh = PrayerResponse::where('id', $response->id)->lockForUpdate()->firstOrFail();

            if ($fresh->trashed() || $fresh->withdrawn_at !== null) {
                throw new ConflictHttpException('Lời nhắn này đã bị rút lại hoặc xóa bởi người gửi.');
            }

            if ($fresh->moderation_status !== PrayerResponseModerationStatus::Pending) {
                throw new ConflictHttpException('Lời nhắn này hiện không ở trạng thái chờ duyệt (trạng thái: '.$fresh->moderation_status->label().').');
            }

            $parent = $fresh->prayerIntention;
            if (! $parent || ! $parent->isPublicApproved()) {
                throw new ConflictHttpException('Lời nguyện của lời nhắn này hiện không còn được chia sẻ công khai.');
            }

            if ($expectedRevision !== null && $fresh->submission_revision !== $expectedRevision) {
                throw new ConflictHttpException('Nội dung lời nhắn đã được người gửi chỉnh sửa trong khi chờ duyệt. Vui lòng làm mới trang để xem phiên bản mới nhất.');
            }

            $oldStatus = $fresh->moderation_status;
            $now = now();

            $fresh->moderation_status = PrayerResponseModerationStatus::Approved;
            $fresh->approved_at = $now;
            $fresh->published_at = $now;
            $fresh->withdrawn_at = null;
            $fresh->rejected_at = null;
            $fresh->revoked_at = null;
            $fresh->moderated_by = $admin->id;
            $fresh->parent_publication_revision = $parent->published_at;
            $fresh->rejection_reason_code = null;
            $fresh->user_rejection_note = null;
            $fresh->save();

            $this->recordAudit(
                $fresh,
                $admin,
                'approved',
                $oldStatus->value,
                PrayerResponseModerationStatus::Approved->value
            );

            $this->invalidateCache($fresh);

            PrayerResponseApproved::dispatch($fresh, $admin);

            return $fresh;
        });
    }

    /**
     * Reject a pending prayer response.
     */
    public function reject(
        User $admin,
        PrayerResponse $response,
        string $reasonCode,
        ?string $userNote = null,
        ?string $adminNotes = null
    ): PrayerResponse {
        return DB::transaction(function () use ($admin, $response, $reasonCode, $userNote, $adminNotes): PrayerResponse {
            /** @var PrayerResponse $fresh */
            $fresh = PrayerResponse::where('id', $response->id)->lockForUpdate()->firstOrFail();

            if ($fresh->trashed() || $fresh->withdrawn_at !== null) {
                throw new ConflictHttpException('Lời nhắn này đã bị rút lại hoặc xóa bởi người gửi.');
            }

            if ($fresh->moderation_status !== PrayerResponseModerationStatus::Pending) {
                throw new ConflictHttpException('Lời nhắn này không ở trạng thái chờ duyệt.');
            }

            $oldStatus = $fresh->moderation_status;
            $now = now();

            $fresh->moderation_status = PrayerResponseModerationStatus::Rejected;
            $fresh->rejected_at = $now;
            $fresh->moderated_by = $admin->id;
            $fresh->rejection_reason_code = $reasonCode;
            $fresh->user_rejection_note = $userNote;
            $fresh->admin_notes = $adminNotes;
            $fresh->save();

            $this->recordAudit(
                $fresh,
                $admin,
                'rejected',
                $oldStatus->value,
                PrayerResponseModerationStatus::Rejected->value,
                $reasonCode
            );

            PrayerResponseRejected::dispatch($fresh, $admin, $reasonCode, $userNote);

            return $fresh;
        });
    }

    /**
     * Revoke a previously approved prayer response.
     */
    public function revoke(
        User $admin,
        PrayerResponse $response,
        string $reasonCode,
        ?string $adminNotes = null
    ): PrayerResponse {
        return DB::transaction(function () use ($admin, $response, $reasonCode, $adminNotes): PrayerResponse {
            /** @var PrayerResponse $fresh */
            $fresh = PrayerResponse::where('id', $response->id)->lockForUpdate()->firstOrFail();

            $oldStatus = $fresh->moderation_status;
            $now = now();

            $fresh->moderation_status = PrayerResponseModerationStatus::Revoked;
            $fresh->revoked_at = $now;
            $fresh->moderated_by = $admin->id;
            $fresh->rejection_reason_code = $reasonCode;
            $fresh->admin_notes = $adminNotes;
            $fresh->save();

            $this->recordAudit(
                $fresh,
                $admin,
                'revoked',
                $oldStatus->value,
                PrayerResponseModerationStatus::Revoked->value,
                $reasonCode
            );

            $this->invalidateCache($fresh);

            PrayerResponseRevoked::dispatch($fresh, $admin, $reasonCode);

            return $fresh;
        });
    }

    /**
     * Dismiss a safety report against a response.
     */
    public function dismissReport(User $admin, PrayerResponseReport $report, ?string $notes = null): PrayerResponseReport
    {
        return DB::transaction(function () use ($admin, $report, $notes): PrayerResponseReport {
            /** @var PrayerResponseReport $freshReport */
            $freshReport = PrayerResponseReport::where('id', $report->id)->lockForUpdate()->firstOrFail();

            $freshReport->status = PrayerResponseReportStatus::Dismissed;
            $freshReport->reviewed_by = $admin->id;
            $freshReport->reviewed_at = now();
            $freshReport->admin_resolution_notes = $notes;
            $freshReport->save();

            if ($freshReport->prayerResponse) {
                $this->recordAudit($freshReport->prayerResponse, $admin, 'report_dismissed');
            }

            return $freshReport;
        });
    }

    /**
     * Action a report by revoking the reported prayer response.
     */
    public function actionReport(User $admin, PrayerResponseReport $report, ?string $notes = null): PrayerResponseReport
    {
        return DB::transaction(function () use ($admin, $report, $notes): PrayerResponseReport {
            /** @var PrayerResponseReport $freshReport */
            $freshReport = PrayerResponseReport::where('id', $report->id)->lockForUpdate()->firstOrFail();

            /** @var PrayerResponse $response */
            $response = PrayerResponse::where('id', $freshReport->prayer_response_id)->lockForUpdate()->firstOrFail();

            if ($response->moderation_status === PrayerResponseModerationStatus::Approved) {
                $this->revoke($admin, $response, $freshReport->reason->value, $notes);
            }

            $freshReport->status = PrayerResponseReportStatus::Actioned;
            $freshReport->reviewed_by = $admin->id;
            $freshReport->reviewed_at = now();
            $freshReport->admin_resolution_notes = $notes;
            $freshReport->save();

            $this->recordAudit($response, $admin, 'report_actioned');

            return $freshReport;
        });
    }

    protected function invalidateCache(PrayerResponse $response): void
    {
        if ($response->prayerIntention) {
            Cache::forget('prayer_responses_'.$response->prayerIntention->public_id);
            Cache::forget('prayer_intentions_public_recent');
        }
    }

    protected function recordAudit(
        PrayerResponse $response,
        User $actor,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $reasonCode = null,
        ?array $metadata = null
    ): PrayerResponseAudit {
        return PrayerResponseAudit::create([
            'prayer_response_id' => $response->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason_code' => $reasonCode,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
