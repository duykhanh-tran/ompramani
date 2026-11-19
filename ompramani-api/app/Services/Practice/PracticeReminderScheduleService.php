<?php

declare(strict_types=1);

namespace App\Services\Practice;

use App\Enums\PracticeReminderScheduleType;
use App\Models\PracticeReminder;
use App\Models\UserNotificationPreference;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class PracticeReminderScheduleService
{
    /**
     * Calculate the next scheduled occurrence in UTC for a reminder starting strictly after $fromUtc.
     */
    public function calculateNextDue(PracticeReminder $reminder, ?CarbonInterface $fromUtc = null): ?Carbon
    {
        $refUtc = $fromUtc ? Carbon::instance($fromUtc)->utc() : now()->utc();
        $timezone = $reminder->timezone ?: 'Asia/Ho_Chi_Minh';
        $timeParts = explode(':', $reminder->local_time);
        $hour = (int) ($timeParts[0] ?? 0);
        $minute = (int) ($timeParts[1] ?? 0);

        return match ($reminder->schedule_type) {
            PracticeReminderScheduleType::Once => $this->calculateNextOnce($reminder, $refUtc, $timezone, $hour, $minute),
            PracticeReminderScheduleType::Daily => $this->calculateNextDaily($reminder, $refUtc, $timezone, $hour, $minute),
            PracticeReminderScheduleType::Weekly => $this->calculateNextWeekly($reminder, $refUtc, $timezone, $hour, $minute),
        };
    }

    /**
     * Calculate next occurrence for a one-time reminder.
     */
    protected function calculateNextOnce(
        PracticeReminder $reminder,
        Carbon $refUtc,
        string $timezone,
        int $hour,
        int $minute
    ): ?Carbon {
        if (! $reminder->local_date) {
            return null;
        }

        $dateStr = $reminder->local_date->format('Y-m-d');
        $timeStr = sprintf('%02d:%02d', $hour, $minute);

        // Check if local datetime exists (not a spring-forward DST gap)
        if ($this->isNonexistentLocalTime($dateStr, $timeStr, $timezone)) {
            return null;
        }

        try {
            $localDt = Carbon::createFromFormat('Y-m-d H:i', "{$dateStr} {$timeStr}", $timezone);
            if (! $localDt) {
                return null;
            }

            $utcDt = $localDt->utc();

            return $utcDt->greaterThan($refUtc) ? $utcDt : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Calculate next occurrence for a daily reminder.
     */
    protected function calculateNextDaily(
        PracticeReminder $reminder,
        Carbon $refUtc,
        string $timezone,
        int $hour,
        int $minute
    ): ?Carbon {
        $localNow = $refUtc->copy()->setTimezone($timezone);
        $candidateDate = $localNow->copy()->startOfDay();

        // Search through upcoming days (up to 14 days)
        for ($i = 0; $i < 14; $i++) {
            $dateStr = $candidateDate->format('Y-m-d');
            $timeStr = sprintf('%02d:%02d', $hour, $minute);

            // Policy Section 25: Skip nonexistent local time on DST spring-forward days
            if (! $this->isNonexistentLocalTime($dateStr, $timeStr, $timezone)) {
                $cand = Carbon::create(
                    $candidateDate->year,
                    $candidateDate->month,
                    $candidateDate->day,
                    $hour,
                    $minute,
                    0,
                    $timezone
                );

                if ($cand) {
                    $candUtc = $cand->copy()->utc();
                    if ($candUtc->greaterThan($refUtc)) {
                        return $candUtc;
                    }
                }
            }

            $candidateDate->addDay();
        }

        return null;
    }

    /**
     * Calculate next occurrence for a weekly reminder with selected ISO weekdays (1=Mon..7=Sun).
     */
    protected function calculateNextWeekly(
        PracticeReminder $reminder,
        Carbon $refUtc,
        string $timezone,
        int $hour,
        int $minute
    ): ?Carbon {
        $weekdays = (array) ($reminder->weekdays ?? []);
        if (empty($weekdays)) {
            return null;
        }

        $localNow = $refUtc->copy()->setTimezone($timezone);
        $candidateDate = $localNow->copy()->startOfDay();

        // Search through upcoming days (up to 35 days)
        for ($i = 0; $i < 35; $i++) {
            if (in_array($candidateDate->isoWeekday(), $weekdays, true)) {
                $dateStr = $candidateDate->format('Y-m-d');
                $timeStr = sprintf('%02d:%02d', $hour, $minute);

                if (! $this->isNonexistentLocalTime($dateStr, $timeStr, $timezone)) {
                    $cand = Carbon::create(
                        $candidateDate->year,
                        $candidateDate->month,
                        $candidateDate->day,
                        $hour,
                        $minute,
                        0,
                        $timezone
                    );

                    if ($cand) {
                        $candUtc = $cand->copy()->utc();
                        if ($candUtc->greaterThan($refUtc)) {
                            return $candUtc;
                        }
                    }
                }
            }

            $candidateDate->addDay();
        }

        return null;
    }

    /**
     * Check if a given local date and time does not exist due to a daylight saving spring-forward jump.
     */
    public function isNonexistentLocalTime(string $dateStr, string $timeStr, string $timezone): bool
    {
        try {
            $dt = Carbon::createFromFormat('Y-m-d H:i', "{$dateStr} {$timeStr}", $timezone);
            if (! $dt) {
                return true;
            }

            // In PHP, if the hour jumped forward (e.g. 02:00 -> 03:00), 02:30 is parsed as 03:30.
            return $dt->format('Y-m-d H:i') !== "{$dateStr} {$timeStr}";
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Check if a scheduled UTC instant falls inside the user's quiet hours.
     */
    public function isQuietHours(CarbonInterface $utcInstant, UserNotificationPreference $settings): bool
    {
        if (! $settings->quiet_hours_enabled) {
            return false;
        }

        $startTime = $settings->quiet_start_local_time;
        $endTime = $settings->quiet_end_local_time;
        $quietTimezone = $settings->quiet_hours_timezone ?: 'Asia/Ho_Chi_Minh';

        if (! $startTime || ! $endTime || $startTime === $endTime) {
            return false;
        }

        $localInstant = Carbon::instance($utcInstant)->setTimezone($quietTimezone);
        $currMinutes = $localInstant->hour * 60 + $localInstant->minute;

        [$startH, $startM] = array_map('intval', explode(':', $startTime));
        [$endH, $endM] = array_map('intval', explode(':', $endTime));

        $startMinutes = $startH * 60 + $startM;
        $endMinutes = $endH * 60 + $endM;

        // Case 1: Same day window (e.g. 13:00 -> 14:00)
        if ($startMinutes < $endMinutes) {
            return $currMinutes >= $startMinutes && $currMinutes < $endMinutes;
        }

        // Case 2: Cross midnight window (e.g. 22:00 -> 06:00)
        return $currMinutes >= $startMinutes || $currMinutes < $endMinutes;
    }
}
