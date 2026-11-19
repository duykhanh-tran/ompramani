<?php

declare(strict_types=1);

namespace App\Services\Prayer;

use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerResponseModerationStatus;
use App\Enums\PrayerResponseReportReason;
use App\Enums\PrayerResponseReportStatus;
use App\Enums\PrayerVisibility;
use App\Models\PrayerIntention;
use App\Models\PrayerIntentionAudit;
use App\Models\PrayerIntentionReport;
use App\Models\PrayerResponse;
use App\Models\PrayerResponseAudit;
use App\Models\PrayerResponseReport;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PrayerRoomService
{
    /**
     * Fetch public community prayer room messages in chronological order.
     * Supports pagination via before_id (older history) or after_id (polling new).
     *
     * @return array{
     *     messages: array<int, array<string, mixed>>,
     *     has_more_older: bool,
     *     has_more_newer: bool,
     *     oldest_id: string|null,
     *     newest_id: string|null
     * }
     */
    public function getMessages(
        ?string $beforeId = null,
        ?string $afterId = null,
        int $limit = 30,
        ?User $viewer = null
    ): array {
        $limit = max(1, min($limit, 50));

        // Find reference timestamp if beforeId or afterId provided
        $referenceTime = null;
        if ($beforeId) {
            $ref = PrayerIntention::where('public_id', $beforeId)->first()
                ?? PrayerResponse::where('public_id', $beforeId)->first();
            if ($ref) {
                $referenceTime = $ref->created_at;
            }
        } elseif ($afterId) {
            $ref = PrayerIntention::where('public_id', $afterId)->first()
                ?? PrayerResponse::where('public_id', $afterId)->first();
            if ($ref) {
                $referenceTime = $ref->created_at;
            }
        }

        // 1. Query PrayerIntentions (Public community prayers)
        $intentionQuery = PrayerIntention::query()
            ->with(['user', 'reports'])
            ->whereNull('withdrawn_at')
            ->where(function ($q) use ($viewer) {
                // Public approved prayers
                $q->where(function ($sub) {
                    $sub->where('visibility', PrayerVisibility::Public->value)
                        ->where('moderation_status', PrayerModerationStatus::Approved->value)
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now());
                });

                // If viewer logged in, also include their own public submissions (even if pending)
                if ($viewer) {
                    $q->orWhere(function ($sub) use ($viewer) {
                        $sub->where('user_id', $viewer->id)
                            ->where('visibility', PrayerVisibility::Public->value);
                    });
                }
            });

        // 2. Query PrayerResponses (Supportive replies)
        $responseQuery = PrayerResponse::query()
            ->with(['user', 'prayerIntention', 'reports'])
            ->whereNull('withdrawn_at')
            ->where(function ($q) use ($viewer) {
                // Public approved responses where parent prayer is also public approved
                $q->where(function ($sub) {
                    $sub->where('moderation_status', PrayerResponseModerationStatus::Approved->value)
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now())
                        ->whereHas('prayerIntention', function ($parent) {
                            $parent->publicApproved();
                        });
                });

                // If viewer logged in, also include their own responses
                if ($viewer) {
                    $q->orWhere(function ($sub) use ($viewer) {
                        $sub->where('user_id', $viewer->id)
                            ->whereHas('prayerIntention', function ($parent) {
                                $parent->publicApproved();
                            });
                    });
                }
            });

        // Apply before / after conditions
        if ($beforeId && $referenceTime) {
            $intentionQuery->where('created_at', '<', $referenceTime);
            $responseQuery->where('created_at', '<', $referenceTime);
            // Fetch in descending order to get the immediate older ones, then reverse
            $intentions = $intentionQuery->orderBy('created_at', 'desc')->limit($limit + 1)->get();
            $responses = $responseQuery->orderBy('created_at', 'desc')->limit($limit + 1)->get();
        } elseif ($afterId && $referenceTime) {
            $intentionQuery->where('created_at', '>', $referenceTime);
            $responseQuery->where('created_at', '>', $referenceTime);
            $intentions = $intentionQuery->orderBy('created_at', 'asc')->limit($limit + 1)->get();
            $responses = $responseQuery->orderBy('created_at', 'asc')->limit($limit + 1)->get();
        } else {
            // Default initial load: get latest messages
            $intentions = $intentionQuery->orderBy('created_at', 'desc')->limit($limit + 1)->get();
            $responses = $responseQuery->orderBy('created_at', 'desc')->limit($limit + 1)->get();
        }

        // Merge and sort
        /** @var Collection<int, PrayerIntention|PrayerResponse> $merged */
        $merged = $intentions->concat($responses);

        if ($afterId && $referenceTime) {
            $sorted = $merged->sortBy('created_at')->values();
        } else {
            $sorted = $merged->sortByDesc('created_at')->values();
        }

        $hasMore = $sorted->count() > $limit;
        $sliced = $sorted->slice(0, $limit);

        // Always return chronologically ascending (oldest first to newest last) for chat stream
        $chronological = $sliced->sortBy('created_at')->values();

        $messageList = $chronological->map(function ($item) use ($viewer): array {
            return $this->formatMessageItem($item, $viewer);
        })->all();

        return [
            'messages' => $messageList,
            'has_more_older' => ($beforeId !== null ? $hasMore : false) || ($beforeId === null && $hasMore),
            'has_more_newer' => $afterId !== null ? $hasMore : false,
            'oldest_id' => ! empty($messageList) ? $messageList[0]['public_id'] : null,
            'newest_id' => ! empty($messageList) ? $messageList[count($messageList) - 1]['public_id'] : null,
        ];
    }

    /**
     * Submit a community message (prayer or reply) with strict consent and sanitization.
     */
    public function sendMessage(
        User $user,
        string $body,
        bool $consentGiven,
        ?string $replyToPublicId = null
    ): array {
        if (! $consentGiven) {
            throw ValidationException::withMessages([
                'consent_public_sharing' => ['Bạn cần xác nhận đồng ý hiển thị công khai trước khi gửi vào phòng lời nguyện.'],
            ]);
        }

        $cleanBody = $this->sanitizeBody($body);
        if (mb_strlen($cleanBody) < 2) {
            throw ValidationException::withMessages([
                'body' => ['Nội dung lời nguyện hoặc lời nhắn quá ngắn (tối thiểu 2 ký tự).'],
            ]);
        }
        if (mb_strlen($cleanBody) > 1000) {
            throw ValidationException::withMessages([
                'body' => ['Nội dung lời nguyện hoặc lời nhắn vượt quá giới hạn 1.000 ký tự.'],
            ]);
        }

        // Anti-spam idempotency: if exact same body submitted in last 30 seconds by same user
        if ($replyToPublicId) {
            $targetIntention = $this->resolveTargetIntention($replyToPublicId);
            $recent = PrayerResponse::where('prayer_intention_id', $targetIntention->id)
                ->where('user_id', $user->id)
                ->where('body', $cleanBody)
                ->where('created_at', '>=', now()->subSeconds(30))
                ->first();

            if ($recent) {
                return $this->formatMessageItem($recent, $user);
            }

            return DB::transaction(function () use ($user, $targetIntention, $cleanBody): array {
                /** @var PrayerIntention $lockedParent */
                $lockedParent = PrayerIntention::where('id', $targetIntention->id)->lockForUpdate()->firstOrFail();
                if (! $lockedParent->isPublicApproved()) {
                    throw new NotFoundHttpException('Lời nguyện này không còn được chia sẻ công khai.');
                }

                $now = now();
                $response = PrayerResponse::create([
                    'prayer_intention_id' => $lockedParent->id,
                    'user_id' => $user->id,
                    'body' => $cleanBody,
                    'moderation_status' => PrayerResponseModerationStatus::Pending,
                    'submitted_at' => $now,
                ]);

                PrayerResponseAudit::create([
                    'prayer_response_id' => $response->id,
                    'actor_id' => $user->id,
                    'action' => 'submitted_in_prayer_room',
                    'from_status' => null,
                    'to_status' => PrayerResponseModerationStatus::Pending->value,
                ]);

                return $this->formatMessageItem($response, $user);
            });
        }

        // Top-level message in Prayer Room (Creates a public PrayerIntention)
        $recentIntention = PrayerIntention::where('user_id', $user->id)
            ->where('body', $cleanBody)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->first();

        if ($recentIntention) {
            return $this->formatMessageItem($recentIntention, $user);
        }

        return DB::transaction(function () use ($user, $cleanBody): array {
            $now = now();
            $intention = PrayerIntention::create([
                'user_id' => $user->id,
                'title' => null,
                'body' => $cleanBody,
                'category' => null,
                'visibility' => PrayerVisibility::Public,
                'moderation_status' => PrayerModerationStatus::Pending,
                'submitted_at' => $now,
            ]);

            PrayerIntentionAudit::create([
                'prayer_intention_id' => $intention->id,
                'actor_id' => $user->id,
                'action' => 'submitted_in_prayer_room',
                'from_visibility' => null,
                'to_visibility' => PrayerVisibility::Public->value,
                'from_status' => null,
                'to_status' => PrayerModerationStatus::Pending->value,
            ]);

            return $this->formatMessageItem($intention, $user);
        });
    }

    /**
     * Delete / withdraw a message (by owner or admin).
     */
    public function deleteMessage(User $user, string $publicId): bool
    {
        // Try PrayerIntention
        $intention = PrayerIntention::where('public_id', $publicId)->first();
        if ($intention) {
            if ($intention->user_id !== $user->id && $user->role !== 'admin') {
                throw new AccessDeniedHttpException('Bạn không có quyền xóa lời nguyện này.');
            }

            return DB::transaction(function () use ($intention, $user): bool {
                $intention->withdrawn_at = now();
                $intention->save();
                $intention->delete();

                PrayerIntentionAudit::create([
                    'prayer_intention_id' => $intention->id,
                    'actor_id' => $user->id,
                    'action' => 'withdrawn_from_prayer_room',
                ]);

                return true;
            });
        }

        // Try PrayerResponse
        $response = PrayerResponse::where('public_id', $publicId)->first();
        if ($response) {
            if ($response->user_id !== $user->id && $user->role !== 'admin') {
                throw new AccessDeniedHttpException('Bạn không có quyền xóa lời nhắn này.');
            }

            return DB::transaction(function () use ($response, $user): bool {
                $response->withdrawn_at = now();
                $response->save();
                $response->delete();

                PrayerResponseAudit::create([
                    'prayer_response_id' => $response->id,
                    'actor_id' => $user->id,
                    'action' => 'withdrawn_from_prayer_room',
                ]);

                return true;
            });
        }

        throw new NotFoundHttpException('Tin nhắn không tồn tại hoặc đã bị xóa.');
    }

    /**
     * Report a community message for moderation.
     */
    public function reportMessage(
        User $reporter,
        string $publicId,
        string $reason,
        ?string $details = null
    ): array {
        $reasonEnum = PrayerResponseReportReason::tryFrom($reason) ?? PrayerResponseReportReason::Other;
        $cleanDetails = $details ? mb_substr(strip_tags(trim($details)), 0, 500) : null;

        // Check if target is a PrayerIntention
        $intention = PrayerIntention::where('public_id', $publicId)->first();
        if ($intention) {
            if ($intention->user_id === $reporter->id) {
                throw ValidationException::withMessages([
                    'report' => ['Bạn không thể tự báo cáo lời nguyện của chính mình.'],
                ]);
            }

            if (! $intention->isPublicApproved()) {
                throw new NotFoundHttpException('Lời nguyện không ở chế độ hiển thị công khai.');
            }

            $existing = PrayerIntentionReport::where('prayer_intention_id', $intention->id)
                ->where('reporter_user_id', $reporter->id)
                ->where('status', PrayerResponseReportStatus::Pending->value)
                ->first();

            if (! $existing) {
                PrayerIntentionReport::create([
                    'prayer_intention_id' => $intention->id,
                    'reporter_user_id' => $reporter->id,
                    'reason' => $reasonEnum,
                    'details' => $cleanDetails,
                    'status' => PrayerResponseReportStatus::Pending,
                ]);

                PrayerIntentionAudit::create([
                    'prayer_intention_id' => $intention->id,
                    'actor_id' => $reporter->id,
                    'action' => 'report_filed',
                    'reason_code' => $reasonEnum->value,
                ]);
            }

            return [
                'success' => true,
                'message' => 'Báo cáo của bạn đã được ghi nhận và chuyển tới Ban Điều Hành.',
            ];
        }

        // Check if target is a PrayerResponse
        $response = PrayerResponse::where('public_id', $publicId)->first();
        if ($response) {
            if ($response->user_id === $reporter->id) {
                throw ValidationException::withMessages([
                    'report' => ['Bạn không thể tự báo cáo lời nhắn của chính mình.'],
                ]);
            }

            if (! $response->isPublicApproved()) {
                throw new NotFoundHttpException('Lời nhắn không ở chế độ hiển thị công khai.');
            }

            $existing = PrayerResponseReport::where('prayer_response_id', $response->id)
                ->where('reporter_user_id', $reporter->id)
                ->where('status', PrayerResponseReportStatus::Pending->value)
                ->first();

            if (! $existing) {
                PrayerResponseReport::create([
                    'prayer_response_id' => $response->id,
                    'reporter_user_id' => $reporter->id,
                    'reason' => $reasonEnum,
                    'details' => $cleanDetails,
                    'status' => PrayerResponseReportStatus::Pending,
                ]);

                PrayerResponseAudit::create([
                    'prayer_response_id' => $response->id,
                    'actor_id' => $reporter->id,
                    'action' => 'report_filed',
                    'reason_code' => $reasonEnum->value,
                ]);
            }

            return [
                'success' => true,
                'message' => 'Báo cáo của bạn đã được ghi nhận và chuyển tới Ban Điều Hành.',
            ];
        }

        throw new NotFoundHttpException('Tin nhắn không tồn tại hoặc đã bị xóa.');
    }

    /**
     * Resolve target PrayerIntention when replying to a message.
     */
    protected function resolveTargetIntention(string $replyToPublicId): PrayerIntention
    {
        $intention = PrayerIntention::where('public_id', $replyToPublicId)->first();
        if ($intention) {
            return $intention;
        }

        $response = PrayerResponse::where('public_id', $replyToPublicId)->with('prayerIntention')->first();
        if ($response && $response->prayerIntention) {
            return $response->prayerIntention;
        }

        throw new NotFoundHttpException('Tin nhắn được trả lời không tồn tại hoặc đã bị xóa.');
    }

    /**
     * Format a PrayerIntention or PrayerResponse into a unified public chat item.
     */
    protected function formatMessageItem(PrayerIntention|PrayerResponse $item, ?User $viewer): array
    {
        $isOwn = $viewer && $item->user_id === $viewer->id;
        $isResponse = $item instanceof PrayerResponse;

        // Safe public display name
        // Rules: Never leak email, phone, full legal name, or private data.
        $displayName = 'Một Phật tử';

        // Moderation status normalized string
        $moderationStatus = match (true) {
            $item->moderation_status instanceof PrayerModerationStatus => $item->moderation_status->value,
            $item->moderation_status instanceof PrayerResponseModerationStatus => $item->moderation_status->value,
            default => (string) $item->moderation_status,
        };

        // Reply context if this is a response
        $replyTo = null;
        if ($isResponse && $item->prayerIntention) {
            $parent = $item->prayerIntention;
            $replyTo = [
                'public_id' => $parent->public_id,
                'display_name' => 'Một Phật tử',
                'body_preview' => mb_substr(strip_tags($parent->body), 0, 75).(mb_strlen($parent->body) > 75 ? '...' : ''),
            ];
        }

        return [
            'public_id' => $item->public_id,
            'type' => $isResponse ? 'response' : 'prayer',
            'body' => $item->body,
            'display_name' => $displayName,
            'is_own' => (bool) $isOwn,
            'moderation_status' => $moderationStatus,
            'created_at' => $item->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'reply_to' => $replyTo,
            'can_delete' => (bool) ($viewer && ($isOwn || $viewer->role === 'admin')),
            'can_report' => (bool) ($viewer && ! $isOwn && $item->isPublicApproved()),
        ];
    }

    /**
     * Helper to sanitize body.
     */
    protected function sanitizeBody(string $raw): string
    {
        $text = strip_tags($raw);
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return trim($text);
    }
}
