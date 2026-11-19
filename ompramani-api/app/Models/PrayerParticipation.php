<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerParticipation extends Model
{
    use HasFactory;

    protected $fillable = [
        'prayer_intention_id',
        'user_id',
        'participated_at',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'participated_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function prayerIntention(): BelongsTo
    {
        return $this->belongsTo(PrayerIntention::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope query to only active participations (not withdrawn).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('withdrawn_at');
    }

    /**
     * Scope query to withdrawn participations.
     */
    public function scopeWithdrawn(Builder $query): Builder
    {
        return $query->whereNotNull('withdrawn_at');
    }

    public function isActive(): bool
    {
        return $this->withdrawn_at === null;
    }
}
