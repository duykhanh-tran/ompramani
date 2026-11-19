<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PracticeReminderDeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeReminderDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'practice_reminder_id',
        'user_id',
        'scheduled_for_utc',
        'schedule_revision',
        'channel',
        'status',
        'notification_id',
        'processed_at',
        'failure_reason_code',
    ];

    protected function casts(): array
    {
        return [
            'status' => PracticeReminderDeliveryStatus::class,
            'scheduled_for_utc' => 'datetime',
            'processed_at' => 'datetime',
            'schedule_revision' => 'integer',
        ];
    }

    public function practiceReminder(): BelongsTo
    {
        return $this->belongsTo(PracticeReminder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
