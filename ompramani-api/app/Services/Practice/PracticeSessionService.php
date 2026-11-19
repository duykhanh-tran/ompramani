<?php

namespace App\Services\Practice;

use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Events\PracticeSessionCancelled;
use App\Events\PracticeSessionCompleted;
use App\Events\PracticeSessionStarted;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PracticeSessionService
{
    /**
     * Maximum credited seconds allowed between consecutive heartbeats (clamped).
     */
    public const MAX_HEARTBEAT_ELAPSED_SECONDS = 120;

    /**
     * Inactivity threshold before an open session is considered stale/expired.
     */
    public const DEFAULT_STALE_INACTIVITY_MINUTES = 30;

    public function __construct(
        protected PracticeTypeRegistry $registry
    ) {}

    /**
     * Start a new practice session for the authenticated user.
     *
     * @param  array{
     *     practice_type: string|PracticeType,
     *     client_session_key?: ?string,
     *     subject_type?: ?string,
     *     subject_id?: ?int,
     *     progress_data?: ?array<string, mixed>
     * }  $data
     *
     * @throws ValidationException
     */
    public function startSession(User $user, array $data): PracticeSession
    {
        // 1. Idempotency Check
        $clientKey = $data['client_session_key'] ?? null;
        if (! empty($clientKey)) {
            $existing = PracticeSession::where('user_id', $user->id)
                ->where('client_session_key', $clientKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        // 2. Validate Practice Type
        $rawType = $data['practice_type'] ?? null;
        if ($rawType instanceof PracticeType) {
            $practiceType = $rawType;
        } elseif (is_string($rawType) && ($case = PracticeType::tryFrom($rawType))) {
            $practiceType = $case;
        } else {
            throw ValidationException::withMessages([
                'practice_type' => 'Loại hình tu tập không hợp lệ.',
            ]);
        }

        // 3. Validate Subject
        $subjectType = $data['subject_type'] ?? null;
        $subjectId = isset($data['subject_id']) ? (int) $data['subject_id'] : null;
        $this->registry->validateAndResolveSubject($practiceType, $subjectType, $subjectId);

        // 4. Validate Progress Data
        $sanitizedProgress = $this->registry->validateAndSanitizeProgressData(
            $practiceType,
            $data['progress_data'] ?? null
        );

        // 5. Concurrency Enforcement under Database Transaction & Row Lock
        return DB::transaction(function () use ($user, $practiceType, $clientKey, $subjectType, $subjectId, $sanitizedProgress): PracticeSession {
            // Auto-expire stale open sessions first
            $this->autoExpireStaleSessionForUser($user->id);

            // Check if user already has an active or paused session
            $hasOpenSession = PracticeSession::where('user_id', $user->id)
                ->whereIn('status', [PracticeSessionStatus::Active, PracticeSessionStatus::Paused])
                ->lockForUpdate()
                ->exists();

            if ($hasOpenSession) {
                throw ValidationException::withMessages([
                    'practice_session' => 'Bạn đang có một phiên tu tập chưa kết thúc. Vui lòng hoàn thành hoặc hủy phiên trước khi bắt đầu phiên mới.',
                ]);
            }

            $now = now();

            $session = PracticeSession::create([
                'user_id' => $user->id,
                'practice_type' => $practiceType,
                'status' => PracticeSessionStatus::Active,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'started_at' => $now,
                'ended_at' => null,
                'active_duration_seconds' => 0,
                'last_heartbeat_at' => $now,
                'last_activity_at' => $now,
                'progress_data' => $sanitizedProgress,
                'client_session_key' => $clientKey,
                'sequence' => 0,
            ]);

            DB::afterCommit(function () use ($session): void {
                PracticeSessionStarted::dispatch($session);
            });

            return $session;
        });
    }

    /**
     * Get the active or paused session for a user, if any.
     */
    public function getActiveSession(User $user): ?PracticeSession
    {
        $this->autoExpireStaleSessionForUser($user->id);

        return PracticeSession::where('user_id', $user->id)
            ->whereIn('status', [PracticeSessionStatus::Active, PracticeSessionStatus::Paused])
            ->latest('started_at')
            ->first();
    }

    /**
     * Get paginated session history for a user with optional filters.
     *
     * @param  array{
     *     practice_type?: ?string,
     *     status?: ?string,
     *     from?: ?string,
     *     to?: ?string,
     *     per_page?: ?int
     * }  $filters
     */
    public function getUserSessions(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = PracticeSession::forUser($user)->latest('started_at');

        if (! empty($filters['practice_type'])) {
            $query->where('practice_type', $filters['practice_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            $query->where('started_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('started_at', '<=', $filters['to']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 12;
        $perPage = max(1, min(50, $perPage));

        return $query->paginate($perPage);
    }

    /**
     * Pause an active practice session.
     *
     * @throws ValidationException
     */
    public function pauseSession(PracticeSession $session, User $user): PracticeSession
    {
        $this->ensureOwnership($session, $user);

        if ($session->status !== PracticeSessionStatus::Active) {
            throw ValidationException::withMessages([
                'status' => "Chỉ có thể tạm dừng phiên đang hoạt động (trạng thái hiện tại: '{$session->status->label()}').",
            ]);
        }

        return DB::transaction(function () use ($session): PracticeSession {
            /** @var PracticeSession $lockedSession */
            $lockedSession = PracticeSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            if ($lockedSession->status !== PracticeSessionStatus::Active) {
                return $lockedSession;
            }

            $now = now();
            $elapsed = $this->calculateClampedElapsed($lockedSession->last_heartbeat_at, $now);

            $lockedSession->active_duration_seconds += $elapsed;
            $lockedSession->status = PracticeSessionStatus::Paused;
            $lockedSession->last_heartbeat_at = null; // Freeze checkpoint
            $lockedSession->last_activity_at = $now;
            $lockedSession->save();

            return $lockedSession;
        });
    }

    /**
     * Resume a paused practice session.
     *
     * @throws ValidationException
     */
    public function resumeSession(PracticeSession $session, User $user): PracticeSession
    {
        $this->ensureOwnership($session, $user);

        if ($session->status !== PracticeSessionStatus::Paused) {
            throw ValidationException::withMessages([
                'status' => "Chỉ có thể tiếp tục phiên đang tạm dừng (trạng thái hiện tại: '{$session->status->label()}').",
            ]);
        }

        return DB::transaction(function () use ($session): PracticeSession {
            /** @var PracticeSession $lockedSession */
            $lockedSession = PracticeSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            if ($lockedSession->status !== PracticeSessionStatus::Paused) {
                return $lockedSession;
            }

            $now = now();
            $lockedSession->status = PracticeSessionStatus::Active;
            $lockedSession->last_heartbeat_at = $now; // Fresh checkpoint
            $lockedSession->last_activity_at = $now;
            $lockedSession->save();

            return $lockedSession;
        });
    }

    /**
     * Record a heartbeat to credit elapsed time safely.
     *
     * @throws ValidationException
     */
    public function heartbeat(PracticeSession $session, User $user): PracticeSession
    {
        $this->ensureOwnership($session, $user);

        if ($session->status !== PracticeSessionStatus::Active) {
            throw ValidationException::withMessages([
                'status' => 'Không thể ghi nhận heartbeat cho phiên không ở trạng thái hoạt động.',
            ]);
        }

        return DB::transaction(function () use ($session): PracticeSession {
            /** @var PracticeSession $lockedSession */
            $lockedSession = PracticeSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            if ($lockedSession->status !== PracticeSessionStatus::Active) {
                return $lockedSession;
            }

            $now = now();
            $elapsed = $this->calculateClampedElapsed($lockedSession->last_heartbeat_at, $now);

            $lockedSession->active_duration_seconds += $elapsed;
            $lockedSession->last_heartbeat_at = $now;
            $lockedSession->last_activity_at = $now;
            $lockedSession->save();

            return $lockedSession;
        });
    }

    /**
     * Update progress data safely with sequence ordering.
     *
     * @param  array<string, mixed>  $progressData
     *
     * @throws ValidationException
     */
    public function updateProgress(
        PracticeSession $session,
        User $user,
        array $progressData,
        ?int $sequence = null
    ): PracticeSession {
        $this->ensureOwnership($session, $user);

        if ($session->status->isTerminal()) {
            throw ValidationException::withMessages([
                'status' => 'Không thể cập nhật tiến trình cho phiên đã kết thúc.',
            ]);
        }

        $sanitized = $this->registry->validateAndSanitizeProgressData($session->practice_type, $progressData);

        return DB::transaction(function () use ($session, $sanitized, $sequence): PracticeSession {
            /** @var PracticeSession $lockedSession */
            $lockedSession = PracticeSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            if ($lockedSession->status->isTerminal()) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể cập nhật tiến trình cho phiên đã kết thúc.',
                ]);
            }

            // Sequence check to prevent stale out-of-order writes
            if ($sequence !== null) {
                if ($sequence <= $lockedSession->sequence) {
                    throw ValidationException::withMessages([
                        'sequence' => 'Cập nhật bị từ chối do sequence nhỏ hơn hoặc bằng sequence hiện tại (stale update).',
                    ]);
                }
                $lockedSession->sequence = $sequence;
            }

            $currentData = $lockedSession->progress_data ?? [];
            $lockedSession->progress_data = array_merge($currentData, $sanitized);
            $lockedSession->last_activity_at = now();
            $lockedSession->save();

            return $lockedSession;
        });
    }

    /**
     * Complete a practice session.
     *
     * @param  array<string, mixed>|null  $finalProgress
     *
     * @throws ValidationException
     */
    public function completeSession(
        PracticeSession $session,
        User $user,
        ?array $finalProgress = null
    ): PracticeSession {
        $this->ensureOwnership($session, $user);

        // Idempotent return if already completed
        if ($session->status === PracticeSessionStatus::Completed) {
            return $session;
        }

        if (! $session->status->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'Không thể hoàn thành phiên đã bị hủy hoặc hết hạn.',
            ]);
        }

        $sanitizedFinal = $finalProgress
            ? $this->registry->validateAndSanitizeProgressData($session->practice_type, $finalProgress)
            : null;

        return DB::transaction(function () use ($session, $sanitizedFinal): PracticeSession {
            /** @var PracticeSession $lockedSession */
            $lockedSession = PracticeSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            if ($lockedSession->status === PracticeSessionStatus::Completed) {
                return $lockedSession;
            }

            if (! $lockedSession->status->isOpen()) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể hoàn thành phiên đã bị hủy hoặc hết hạn.',
                ]);
            }

            $now = now();

            // Finalize remaining active duration if was active
            if ($lockedSession->status === PracticeSessionStatus::Active) {
                $elapsed = $this->calculateClampedElapsed($lockedSession->last_heartbeat_at, $now);
                $lockedSession->active_duration_seconds += $elapsed;
            }

            if ($sanitizedFinal !== null) {
                $currentData = $lockedSession->progress_data ?? [];
                $lockedSession->progress_data = array_merge($currentData, $sanitizedFinal);
            }

            $lockedSession->status = PracticeSessionStatus::Completed;
            $lockedSession->ended_at = $now;
            $lockedSession->last_heartbeat_at = null;
            $lockedSession->last_activity_at = $now;
            $lockedSession->save();

            DB::afterCommit(function () use ($lockedSession): void {
                PracticeSessionCompleted::dispatch($lockedSession);
            });

            return $lockedSession;
        });
    }

    /**
     * Cancel an ongoing practice session.
     *
     * @throws ValidationException
     */
    public function cancelSession(PracticeSession $session, User $user): PracticeSession
    {
        $this->ensureOwnership($session, $user);

        // Idempotent return if already cancelled
        if ($session->status === PracticeSessionStatus::Cancelled) {
            return $session;
        }

        if (! $session->status->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'Không thể hủy phiên đã kết thúc.',
            ]);
        }

        return DB::transaction(function () use ($session): PracticeSession {
            /** @var PracticeSession $lockedSession */
            $lockedSession = PracticeSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            if ($lockedSession->status === PracticeSessionStatus::Cancelled) {
                return $lockedSession;
            }

            if (! $lockedSession->status->isOpen()) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể hủy phiên đã kết thúc.',
                ]);
            }

            $now = now();
            $lockedSession->status = PracticeSessionStatus::Cancelled;
            $lockedSession->ended_at = $now;
            $lockedSession->last_heartbeat_at = null;
            $lockedSession->last_activity_at = $now;
            $lockedSession->save();

            DB::afterCommit(function () use ($lockedSession): void {
                PracticeSessionCancelled::dispatch($lockedSession);
            });

            return $lockedSession;
        });
    }

    /**
     * Auto-expire stale open sessions for a specific user.
     */
    public function autoExpireStaleSessionForUser(int $userId, int $inactivityMinutes = self::DEFAULT_STALE_INACTIVITY_MINUTES): void
    {
        $cutoff = now()->subMinutes($inactivityMinutes);

        PracticeSession::where('user_id', $userId)
            ->whereIn('status', [PracticeSessionStatus::Active, PracticeSessionStatus::Paused])
            ->where('last_activity_at', '<', $cutoff)
            ->update([
                'status' => PracticeSessionStatus::Expired,
                'ended_at' => DB::raw('last_activity_at'),
                'last_heartbeat_at' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Expire all stale open sessions across the entire system (for Artisan cron).
     */
    public function expireStaleSessions(int $inactivityMinutes = self::DEFAULT_STALE_INACTIVITY_MINUTES): int
    {
        $cutoff = now()->subMinutes($inactivityMinutes);

        return PracticeSession::whereIn('status', [PracticeSessionStatus::Active, PracticeSessionStatus::Paused])
            ->where('last_activity_at', '<', $cutoff)
            ->update([
                'status' => PracticeSessionStatus::Expired,
                'ended_at' => DB::raw('last_activity_at'),
                'last_heartbeat_at' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Calculate clamped elapsed seconds between a checkpoint and now.
     */
    protected function calculateClampedElapsed(?CarbonInterface $checkpoint, CarbonInterface $now): int
    {
        if (! $checkpoint) {
            return 0;
        }

        $diff = $now->getTimestamp() - $checkpoint->getTimestamp();

        if ($diff <= 0) {
            return 0;
        }

        return min($diff, self::MAX_HEARTBEAT_ELAPSED_SECONDS);
    }

    /**
     * Ensure the given user owns the session.
     *
     * @throws ValidationException
     */
    protected function ensureOwnership(PracticeSession $session, User $user): void
    {
        if ($session->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền thao tác trên phiên tu tập này.');
        }
    }
}
