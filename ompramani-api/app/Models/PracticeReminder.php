<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PracticeReminderScheduleType;
use App\Enums\PracticeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PracticeReminder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'practice_type',
        'title',
        'schedule_type',
        'local_time',
        'timezone',
        'local_date',
        'weekdays',
        'is_enabled',
        'schedule_revision',
        'next_due_at_utc',
        'last_scheduled_at_utc',
    ];

    protected function casts(): array
    {
        return [
            'practice_type' => PracticeType::class,
            'schedule_type' => PracticeReminderScheduleType::class,
            'weekdays' => 'array',
            'is_enabled' => 'boolean',
            'schedule_revision' => 'integer',
            'local_date' => 'date:Y-m-d',
            'next_due_at_utc' => 'datetime',
            'last_scheduled_at_utc' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PracticeReminderDelivery::class);
    }

    /**
     * Scope reminders that are enabled and currently due for dispatch.
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_enabled', true)
            ->whereNotNull('next_due_at_utc')
            ->where('next_due_at_utc', '<=', now());
    }

    /**
     * Scope reminders belonging to a given user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Resolve the safe internal destination route for this reminder.
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
            default => '/tai-khoan/nhac-nho-tu-tap',
        };
    }

    /**
     * Human-friendly Vietnamese description of the recurrence schedule.
     */
    public function scheduleDescription(): string
    {
        $timeStr = $this->local_time;
        $tzStr = $this->timezone;

        if ($this->schedule_type === PracticeReminderScheduleType::Once) {
            $dateStr = $this->local_date ? $this->local_date->format('d/m/Y') : '';

            return "Một lần vào ngày {$dateStr} lúc {$timeStr}";
        }

        if ($this->schedule_type === PracticeReminderScheduleType::Daily) {
            return "Hằng ngày lúc {$timeStr}";
        }

        if ($this->schedule_type === PracticeReminderScheduleType::Weekly) {
            $weekdayMap = [
                1 => 'Thứ Hai',
                2 => 'Thứ Ba',
                3 => 'Thứ Tư',
                4 => 'Thứ Năm',
                5 => 'Thứ Sáu',
                6 => 'Thứ Bảy',
                7 => 'Chủ Nhật',
            ];

            $selectedDays = array_map(fn ($d) => $weekdayMap[$d] ?? "T{$d}", $this->weekdays ?? []);
            $daysStr = implode(', ', $selectedDays);

            return "Hằng tuần vào {$daysStr} lúc {$timeStr}";
        }

        return "{$this->schedule_type->label()} lúc {$timeStr}";
    }
}
