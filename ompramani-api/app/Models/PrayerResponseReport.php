<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrayerResponseReportReason;
use App\Enums\PrayerResponseReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerResponseReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'prayer_response_id',
        'reporter_user_id',
        'reason',
        'details',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'reason' => PrayerResponseReportReason::class,
            'status' => PrayerResponseReportStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function prayerResponse(): BelongsTo
    {
        return $this->belongsTo(PrayerResponse::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PrayerResponseReportStatus::Pending->value);
    }

    public function scopeReviewed(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PrayerResponseReportStatus::Dismissed->value,
            PrayerResponseReportStatus::Actioned->value,
        ]);
    }
}
