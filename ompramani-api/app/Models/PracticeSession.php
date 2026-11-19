<?php

namespace App\Models;

use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Services\Practice\PracticeTypeRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'practice_type',
        'status',
        'subject_type',
        'subject_id',
        'started_at',
        'ended_at',
        'active_duration_seconds',
        'last_heartbeat_at',
        'last_activity_at',
        'progress_data',
        'client_session_key',
        'sequence',
    ];

    protected $casts = [
        'practice_type' => PracticeType::class,
        'status' => PracticeSessionStatus::class,
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'progress_data' => 'array',
        'active_duration_seconds' => 'integer',
        'sequence' => 'integer',
    ];

    /**
     * The user who owns this practice session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve the associated Buddhist content subject model.
     */
    public function getSubjectModel(): ?Model
    {
        if (! $this->subject_type || ! $this->subject_id) {
            return null;
        }

        $registry = app(PracticeTypeRegistry::class);
        $modelClass = $registry->resolveSubjectModelClass($this->subject_type);

        if (! $modelClass) {
            return null;
        }

        return $modelClass::find($this->subject_id);
    }

    /**
     * Scope query to sessions that are open (active or paused).
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [PracticeSessionStatus::Active, PracticeSessionStatus::Paused]);
    }

    /**
     * Scope query to sessions that are currently active.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PracticeSessionStatus::Active);
    }

    /**
     * Scope query for a specific user.
     */
    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    /**
     * Calculate completed rounds for mala (108 beads per round).
     */
    public function getCompletedRoundsAttribute(): ?int
    {
        if ($this->practice_type !== PracticeType::Mala) {
            return null;
        }

        $beadCount = $this->progress_data['bead_count'] ?? null;
        if ($beadCount === null) {
            return null;
        }

        return intdiv((int) $beadCount, 108);
    }

    /**
     * Calculate remaining beads in current round for mala.
     */
    public function getRemainingBeadsAttribute(): ?int
    {
        if ($this->practice_type !== PracticeType::Mala) {
            return null;
        }

        $beadCount = $this->progress_data['bead_count'] ?? null;
        if ($beadCount === null) {
            return null;
        }

        return ((int) $beadCount) % 108;
    }

    /**
     * Derive computed metrics for API representation.
     *
     * @return array<string, mixed>
     */
    public function deriveMetrics(): array
    {
        if ($this->practice_type === PracticeType::Mala) {
            $beadCount = (int) ($this->progress_data['bead_count'] ?? 0);

            return [
                'bead_count' => $beadCount,
                'completed_rounds' => intdiv($beadCount, 108),
                'remaining_beads' => $beadCount % 108,
            ];
        }

        if ($this->practice_type === PracticeType::BellWoodenFish) {
            return [
                'bell_count' => (int) ($this->progress_data['bell_count'] ?? 0),
                'wooden_fish_count' => (int) ($this->progress_data['wooden_fish_count'] ?? 0),
            ];
        }

        if ($this->practice_type === PracticeType::MantraRecitation || $this->practice_type === PracticeType::BuddhaNameRecitation) {
            return [
                'repetition_count' => (int) ($this->progress_data['repetition_count'] ?? 0),
                'target_repetitions' => isset($this->progress_data['target_repetitions']) ? (int) $this->progress_data['target_repetitions'] : null,
            ];
        }

        return [];
    }
}
