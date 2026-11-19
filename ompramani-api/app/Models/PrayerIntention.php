<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrayerCategory;
use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerResponseModerationStatus;
use App\Enums\PrayerVisibility;
use Database\Factories\PrayerIntentionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PrayerIntention extends Model
{
    /** @use HasFactory<PrayerIntentionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'public_id',
        'user_id',
        'title',
        'body',
        'category',
        'visibility',
        'moderation_status',
        'submitted_at',
        'approved_at',
        'published_at',
        'withdrawn_at',
        'rejected_at',
        'moderated_by',
        'rejection_reason_code',
        'user_rejection_note',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => PrayerVisibility::class,
            'moderation_status' => PrayerModerationStatus::class,
            'category' => PrayerCategory::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PrayerIntention $intention): void {
            if (empty($intention->public_id)) {
                $intention->public_id = (string) Str::ulid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PrayerIntentionAudit::class)->orderBy('id', 'desc');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(PrayerParticipation::class);
    }

    public function activeParticipations(): HasMany
    {
        return $this->hasMany(PrayerParticipation::class)->whereNull('withdrawn_at');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(PrayerResponse::class)->orderBy('id', 'desc');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PrayerIntentionReport::class)->orderBy('id', 'desc');
    }

    public function approvedResponses(): HasMany
    {
        return $this->hasMany(PrayerResponse::class)
            ->where('moderation_status', PrayerResponseModerationStatus::Approved->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNull('withdrawn_at')
            ->whereNull('deleted_at');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope for public approved intentions strictly meeting all publication invariants.
     */
    public function scopePublicApproved(Builder $query): Builder
    {
        return $query->where('visibility', PrayerVisibility::Public->value)
            ->where('moderation_status', PrayerModerationStatus::Approved->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNull('withdrawn_at')
            ->whereNull('deleted_at');
    }

    /**
     * Scope for intentions pending moderation.
     */
    public function scopePendingModeration(Builder $query): Builder
    {
        return $query->where('visibility', PrayerVisibility::Public->value)
            ->where('moderation_status', PrayerModerationStatus::Pending->value)
            ->whereNull('withdrawn_at')
            ->whereNull('deleted_at');
    }

    /**
     * Scope for a specific owner.
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
        return $this->visibility === PrayerVisibility::Public
            && $this->moderation_status === PrayerModerationStatus::Approved
            && $this->published_at !== null
            && $this->published_at <= now()
            && $this->withdrawn_at === null
            && $this->deleted_at === null;
    }

    public function isPending(): bool
    {
        return $this->visibility === PrayerVisibility::Public
            && $this->moderation_status === PrayerModerationStatus::Pending
            && $this->withdrawn_at === null;
    }

    public function isPrivate(): bool
    {
        return $this->visibility === PrayerVisibility::Private;
    }

    public function isRejected(): bool
    {
        return $this->moderation_status === PrayerModerationStatus::Rejected;
    }
}
