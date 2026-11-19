<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'moderation_updates_enabled',
        'new_response_updates_enabled',
        'participation_digest_enabled',
        'practice_reminders_enabled',
        'quiet_hours_enabled',
        'quiet_start_local_time',
        'quiet_end_local_time',
        'quiet_hours_timezone',
    ];

    protected function casts(): array
    {
        return [
            'moderation_updates_enabled' => 'boolean',
            'new_response_updates_enabled' => 'boolean',
            'participation_digest_enabled' => 'boolean',
            'practice_reminders_enabled' => 'boolean',
            'quiet_hours_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create effective preferences for a given user with safe defaults.
     */
    public static function forUser(User $user): self
    {
        /** @var self $preference */
        $preference = static::firstOrCreate(
            ['user_id' => $user->id],
            [
                'moderation_updates_enabled' => true,
                'new_response_updates_enabled' => false,
                'participation_digest_enabled' => false,
                'practice_reminders_enabled' => false,
                'quiet_hours_enabled' => false,
                'quiet_start_local_time' => '22:00',
                'quiet_end_local_time' => '06:00',
                'quiet_hours_timezone' => 'Asia/Ho_Chi_Minh',
            ]
        );

        return $preference;
    }
}
