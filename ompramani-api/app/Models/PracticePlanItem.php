<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PracticePlanScheduleType;
use App\Enums\PracticeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PracticePlanItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'practice_plan_id',
        'user_id',
        'practice_type',
        'title',
        'schedule_type',
        'local_date',
        'local_time',
        'weekdays',
        'target_duration_seconds',
        'target_repetitions',
        'starts_on',
        'ends_on',
        'sort_order',
        'is_enabled',
        'schedule_revision',
        'practice_reminder_id',
    ];

    protected function casts(): array
    {
        return [
            'practice_type' => PracticeType::class,
            'schedule_type' => PracticePlanScheduleType::class,
            'weekdays' => 'array',
            'local_date' => 'date:Y-m-d',
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'schedule_revision' => 'integer',
            'target_duration_seconds' => 'integer',
            'target_repetitions' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PracticePlan::class, 'practice_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(PracticeReminder::class, 'practice_reminder_id');
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(PracticePlanOccurrenceOverride::class);
    }

    public function linkedSessions(): HasMany
    {
        return $this->hasMany(PracticePlanOccurrenceSession::class);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Resolve tool URL for this practice type.
     */
    public function destinationUrl(): string
    {
        return match ($this->practice_type) {
            PracticeType::Meditation => '/thien',
            PracticeType::Mala => '/trang-hat',
            PracticeType::BellWoodenFish => '/chuong-mo',
            PracticeType::BuddhaNameRecitation => '/niem-phat',
            PracticeType::MantraRecitation => '/tri-chu',
            PracticeType::SutraReading, PracticeType::SutraRecitation => '/kinh',
        };
    }

    /**
     * Generate human-readable Vietnamese schedule description.
     */
    public function scheduleDescription(): string
    {
        $timeStr = $this->local_time ? " lúc {$this->local_time}" : '';

        if ($this->schedule_type === PracticePlanScheduleType::Once) {
            $dateStr = $this->local_date ? $this->local_date->format('d/m/Y') : 'Chưa định ngày';

            return "Một lần vào ngày {$dateStr}{$timeStr}";
        }

        $weekdays = $this->weekdays ?? [];
        if (count($weekdays) === 7) {
            return "Hằng ngày{$timeStr}";
        }

        $weekdayNames = [
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
            7 => 'Chủ Nhật',
        ];

        $named = array_map(fn ($d) => $weekdayNames[$d] ?? "T{$d}", $weekdays);

        return implode(', ', $named)."{$timeStr}";
    }
}
