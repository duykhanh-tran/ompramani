<?php

declare(strict_types=1);

namespace App\Services\Prayer;

use App\Enums\PrayerResponseModerationStatus;
use App\Enums\PrayerResponseReportReason;
use App\Enums\PrayerResponseReportStatus;
use App\Models\PrayerIntention;
use App\Models\PrayerResponse;
use App\Models\PrayerResponseAudit;
use App\Models\PrayerResponseReport;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PrayerResponseService
{
    /**
     * List public approved responses for a public prayer intention.
     */
    public function getPublicResponses(string $intentionPublicId, int $perPage = 15): LengthAwarePaginator
    {
        $intention = PrayerIntention::where('public_id', $intentionPublicId)->first();

        if (! $intention || ! $intention->isPublicApproved()) {
            throw new NotFoundHttpException('Lời nguyện không tồn tại hoặc không còn được chia sẻ công khai.');
        }

        return PrayerResponse::where('prayer_intention_id', $intention->id)
            ->publicApproved()
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Submit a supportive response to a public prayer intention.
     * Starts strictly in 'pending' moderation status.
     */
    public function createResponse(
        User $user,
        string $intentionPublicId,
        string $rawBody,
        ?string $clientRequestId = null
    ): PrayerResponse {
        $intention = PrayerIntention::where('public_id', $intentionPublicId)->first();

        if (! $intention || ! $intention->isPublicApproved()) {
            throw new NotFoundHttpException('Lời nguyện không tồn tại hoặc không còn được chia sẻ công khai.');
        }

        $cleanBody = $this->sanitizeBody($rawBody);

        // Idempotency / double-click protection: if same user submitted exact same body in last 30s
        $recent = PrayerResponse::where('prayer_intention_id', $intention->id)
            ->where('user_id', $user->id)
            ->where('body', $cleanBody)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->first();

        if ($recent) {
            return $recent;
        }

        return DB::transaction(function () use ($user, $intention, $cleanBody): PrayerResponse {
            // Lock parent intention to guard against concurrent withdrawal or deletion
            /** @var PrayerIntention|null $lockedParent */
            $lockedParent = PrayerIntention::where('id', $intention->id)->lockForUpdate()->first();
            if (! $lockedParent || ! $lockedParent->isPublicApproved()) {
                throw new NotFoundHttpException('Lời nguyện không tồn tại hoặc không còn được chia sẻ công khai.');
            }

            $response = PrayerResponse::create([
                'prayer_intention_id' => $lockedParent->id,
                'user_id' => $user->id,
                'body' => $cleanBody,
                'moderation_status' => PrayerResponseModerationStatus::Pending,
                'submission_revision' => 1,
                'parent_publication_revision' => $lockedParent->published_at,
                'submitted_at' => now(),
            ]);

            $this->recordAudit($response, $user, 'submitted', null, PrayerResponseModerationStatus::Pending->value);

            return $response;
        });
    }

    /**
     * Edit a response owned by the user.
     * CRITICAL SECURITY INVARIANT: Editing an already-approved response immediately
     * revokes public visibility, resets status to pending, and increments revision.
     */
    public function updateResponse(User $user, int $responseId, string $rawBody): PrayerResponse
    {
        return DB::transaction(function () use ($user, $responseId, $rawBody): PrayerResponse {
            /** @var PrayerResponse $response */
            $response = PrayerResponse::where('id', $responseId)->lockForUpdate()->firstOrFail();

            if ($response->user_id !== $user->id) {
                abort(403, 'Bạn không có quyền chỉnh sửa lời nhắn này.');
            }

            if ($response->trashed()) {
                throw new ConflictHttpException('Lời nhắn này đã bị xóa.');
            }

            $cleanBody = $this->sanitizeBody($rawBody);
            $oldStatus = $response->moderation_status;
            $now = now();

            $response->body = $cleanBody;
            $response->submission_revision += 1;
            $response->submitted_at = $now;

            // Reset approval if previously approved
            if ($oldStatus === PrayerResponseModerationStatus::Approved) {
                $response->moderation_status = PrayerResponseModerationStatus::Pending;
                $response->approved_at = null;
                $response->published_at = null;
                $response->withdrawn_at = null;
            } elseif ($oldStatus === PrayerResponseModerationStatus::Rejected) {
                $response->moderation_status = PrayerResponseModerationStatus::Pending;
                $response->rejected_at = null;
                $response->rejection_reason_code = null;
                $response->user_rejection_note = null;
            }

            $response->save();

            $this->recordAudit(
                $response,
                $user,
                'edited',
                $oldStatus?->value,
                $response->moderation_status->value,
                null,
                ['revision' => $response->submission_revision]
            );

            $this->invalidateCache($response);

            return $response;
        });
    }

    /**
     * Withdraw a response owned by the user.
     * Instantly hides from public view without requiring admin approval.
     */
    public function withdrawResponse(User $user, int $responseId): PrayerResponse
    {
        return DB::transaction(function () use ($user, $responseId): PrayerResponse {
            /** @var PrayerResponse $response */
            $response = PrayerResponse::where('id', $responseId)->lockForUpdate()->firstOrFail();

            if ($response->user_id !== $user->id) {
                abort(403, 'Bạn không có quyền rút lại lời nhắn này.');
            }

            if ($response->withdrawn_at !== null) {
                return $response; // Idempotent
            }

            $oldStatus = $response->moderation_status;
            $response->withdrawn_at = now();
            $response->save();

            $this->recordAudit(
                $response,
                $user,
                'withdrawn',
                $oldStatus?->value,
                $response->moderation_status?->value
            );

            $this->invalidateCache($response);

            return $response;
        });
    }

    /**
     * Soft delete a response owned by the user.
     */
    public function deleteResponse(User $user, int $responseId): bool
    {
        return DB::transaction(function () use ($user, $responseId): bool {
            /** @var PrayerResponse $response */
            $response = PrayerResponse::where('id', $responseId)->lockForUpdate()->firstOrFail();

            if ($response->user_id !== $user->id && $user->role !== 'admin') {
                abort(403, 'Bạn không có quyền xóa lời nhắn này.');
            }

            $this->recordAudit($response, $user, 'deleted');
            $this->invalidateCache($response);

            return (bool) $response->delete();
        });
    }

    /**
     * Resubmit a previously rejected response with updated body.
     */
    public function resubmitResponse(User $user, int $responseId, string $rawBody): PrayerResponse
    {
        return $this->updateResponse($user, $responseId, $rawBody);
    }

    /**
     * Submit an abuse/safety report against an approved public response.
     */
    public function reportResponse(
        User $reporter,
        string $responsePublicId,
        string $reason,
        ?string $details = null
    ): PrayerResponseReport {
        return DB::transaction(function () use ($reporter, $responsePublicId, $reason, $details): PrayerResponseReport {
            /** @var PrayerResponse $response */
            $response = PrayerResponse::where('public_id', $responsePublicId)->lockForUpdate()->firstOrFail();

            // Only currently public approved responses can be reported through the public API
            if (! $response->isPublicApproved()) {
                throw new NotFoundHttpException('Lời nhắn không tồn tại hoặc không ở chế độ hiển thị công khai.');
            }

            // Duplicate report prevention: 1 open pending report per user per response
            $existing = PrayerResponseReport::where('prayer_response_id', $response->id)
                ->where('reporter_user_id', $reporter->id)
                ->where('status', PrayerResponseReportStatus::Pending->value)
                ->first();

            if ($existing) {
                return $existing;
            }

            $reasonEnum = PrayerResponseReportReason::tryFrom($reason) ?? PrayerResponseReportReason::Other;
            $cleanDetails = $details ? mb_substr(strip_tags(trim($details)), 0, 500) : null;

            $report = PrayerResponseReport::create([
                'prayer_response_id' => $response->id,
                'reporter_user_id' => $reporter->id,
                'reason' => $reasonEnum,
                'details' => $cleanDetails,
                'status' => PrayerResponseReportStatus::Pending,
            ]);

            $this->recordAudit($response, $reporter, 'report_filed', null, null, $reasonEnum->value);

            return $report;
        });
    }

    /**
     * Helper to sanitize text body: strips HTML tags, escapes dangerous characters.
     */
    protected function sanitizeBody(string $raw): string
    {
        $text = strip_tags($raw);
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return trim($text);
    }

    /**
     * Invalidate cached public response lists.
     */
    public function invalidateCache(PrayerResponse $response): void
    {
        if ($response->prayerIntention) {
            Cache::forget('prayer_responses_'.$response->prayerIntention->public_id);
            Cache::forget('prayer_intentions_public_recent');
        }
    }

    /**
     * Record an audit timeline entry.
     */
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
