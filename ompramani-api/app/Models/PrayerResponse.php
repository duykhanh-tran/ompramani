<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrayerResponseModerationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PrayerResponse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'public_id',
        'prayer_intention_id',
        'user_id',
        'body',
        'moderation_status',
        'submission_revision',
        'parent_publication_revision',
        'submitted_at',
        'approved_at',
        'published_at',
        'withdrawn_at',
        'rejected_at',
        'revoked_at',
        'moderated_by',
        'rejection_reason_code',
        'user_rejection_note',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'moderation_status' => PrayerResponseModerationStatus::class,
            'submission_revision' => 'integer',
            'parent_publication_revision' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'rejected_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PrayerResponse $response): void {
            if (empty($response->public_id)) {
                $response->public_id = (string) Str::ulid();
            }
        });
    }

    public function prayerIntention(): BelongsTo
    {
        return $this->belongsTo(PrayerIntention::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PrayerResponseReport::class)->orderBy('id', 'desc');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PrayerResponseAudit::class)->orderBy('id', 'desc');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope for strictly approved and public responses.
     * Enforces the publication invariant that the parent PrayerIntention
     * MUST also be currently public approved and matching publication lifecycle.
     */
    public function scopePublicApproved(Builder $query): Builder
    {
        return $query->where('moderation_status', PrayerResponseModerationStatus::Approved->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNull('withdrawn_at')
            ->whereNull('deleted_at')
            ->whereHas('prayerIntention', function (Builder $parentQuery): void {
                $parentQuery->publicApproved();
            });
    }

    /**
     * Scope for pending moderation queue.
     */
    public function scopePendingModeration(Builder $query): Builder
    {
        return $query->where('moderation_status', PrayerResponseModerationStatus::Pending->value)
            ->whereNull('withdrawn_at')
            ->whereNull('deleted_at');
    }

    /**
     * Scope for a specific author.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isPublicApproved(): bool
    {
        return $this->moderation_status === PrayerResponseModerationStatus::Approved
            && $this->published_at !== null
            && $this->published_at <= now()
            && $this->withdrawn_at === null
            && $this->deleted_at === null
            && ($this->prayerIntention && $this->prayerIntention->isPublicApproved());
    }

    public function isPending(): bool
    {
        return $this->moderation_status === PrayerResponseModerationStatus::Pending
            && $this->withdrawn_at === null
            && $this->deleted_at === null;
    }

    public function isRejected(): bool
    {
        return $this->moderation_status === PrayerResponseModerationStatus::Rejected;
    }

    public function isRevoked(): bool
    {
        return $this->moderation_status === PrayerResponseModerationStatus::Revoked;
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn_at !== null;
    }
}
