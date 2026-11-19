<?php

declare(strict_types=1);

namespace App\Services\Practice;

use App\Enums\PracticeReminderDeliveryStatus;
use App\Enums\PracticeReminderScheduleType;
use App\Enums\PrayerNotificationType;
use App\Models\PracticeReminder;
use App\Models\PracticeReminderDelivery;
use App\Notifications\PrayerCommunityNotification;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PracticeReminderDispatchService
{
    public function __construct(
        protected PracticeReminderScheduleService $scheduleService
    ) {}

    /**
     * Dispatch due reminders up to the given limit.
     *
     * @return array<string, int>
     */
    public function dispatchDueReminders(int $limit = 100): array
    {
        $dueReminders = PracticeReminder::where('is_enabled', true)
            ->whereNotNull('next_due_at_utc')
            ->where('next_due_at_utc', '<=', now())
            ->orderBy('next_due_at_utc')
            ->limit($limit)
            ->get();

        $stats = [
            'total_due' => $dueReminders->count(),
            'sent' => 0,
            'skipped_disabled' => 0,
            'skipped_quiet_hours' => 0,
            'skipped_late' => 0,
            'failed' => 0,
        ];

        foreach ($dueReminders as $reminder) {
            $result = $this->processReminder($reminder);
            if (isset($stats[$result])) {
                $stats[$result]++;
            }
        }

        return $stats;
    }

    /**
     * Process an individual due reminder in a safe database transaction.
     */
    public function processReminder(PracticeReminder $reminder): string
    {
        return DB::transaction(function () use ($reminder): string {
            /** @var PracticeReminder|null $fresh */
            $fresh = PracticeReminder::where('id', $reminder->id)->lockForUpdate()->first();

            if (! $fresh || ! $fresh->is_enabled || $fresh->next_due_at_utc === null || $fresh->next_due_at_utc > now()) {
                return 'skipped';
            }

            $dueInstant = $fresh->next_due_at_utc;
            $revision = $fresh->schedule_revision;

            // Atomic delivery claim via unique database constraint
            try {
                /** @var PracticeReminderDelivery $delivery */
                $delivery = PracticeReminderDelivery::create([
                    'practice_reminder_id' => $fresh->id,
                    'user_id' => $fresh->user_id,
                    'scheduled_for_utc' => $dueInstant,
                    'schedule_revision' => $revision,
                    'channel' => 'database',
                    'status' => PracticeReminderDeliveryStatus::Processing,
                ]);
            } catch (QueryException $e) {
                // Duplicate occurrence already claimed
                return 'duplicate';
            }

            $user = $fresh->user;
            if (! $user) {
                $delivery->update([
                    'status' => PracticeReminderDeliveryStatus::Failed,
                    'failure_reason_code' => 'user_not_found',
                    'processed_at' => now(),
                ]);
                $this->advanceReminder($fresh, $dueInstant);

                return 'failed';
            }

            $preferences = $user->getEffectiveNotificationPreferences();

            // 1. Check global practice reminders preference
            if (! $preferences->practice_reminders_enabled) {
                $delivery->update([
                    'status' => PracticeReminderDeliveryStatus::SkippedDisabled,
                    'failure_reason_code' => 'global_preference_disabled',
                    'processed_at' => now(),
                ]);
                $this->advanceReminder($fresh, $dueInstant);

                return 'skipped_disabled';
            }

            // 2. Check missed occurrence grace window (15 minutes)
            if ($dueInstant < now()->subMinutes(15)) {
                $delivery->update([
                    'status' => PracticeReminderDeliveryStatus::SkippedLate,
                    'failure_reason_code' => 'grace_window_expired',
                    'processed_at' => now(),
                ]);
                $this->advanceReminder($fresh, $dueInstant);

                return 'skipped_late';
            }

            // 3. Check quiet hours
            if ($this->scheduleService->isQuietHours($dueInstant, $preferences)) {
                $delivery->update([
                    'status' => PracticeReminderDeliveryStatus::SkippedQuietHours,
                    'failure_reason_code' => 'quiet_hours',
                    'processed_at' => now(),
                ]);
                $this->advanceReminder($fresh, $dueInstant);

                return 'skipped_quiet_hours';
            }

            // 4. Deliver In-App Database Notification
            try {
                $idempotencyKey = "practice_reminder:{$fresh->id}:{$dueInstant->timestamp}:{$revision}";

                $user->notify(new PrayerCommunityNotification(
                    PrayerNotificationType::PersonalPracticeReminder,
                    'practice_reminder',
                    (string) $fresh->id,
                    [
                        'practice_type' => $fresh->practice_type->value,
                        'practice_label' => $fresh->practice_type->label(),
                        'title' => $fresh->title ?: $fresh->practice_type->label(),
                        'message' => 'Đã đến thời gian thực hành '.mb_strtolower($fresh->practice_type->label()).' bạn đã lựa chọn.',
                        'destination' => $fresh->destinationUrl(),
                        'idempotency_key' => $idempotencyKey,
                    ]
                ));

                $delivery->update([
                    'status' => PracticeReminderDeliveryStatus::Sent,
                    'processed_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Practice reminder notification delivery failed: '.$e->getMessage(), [
                    'reminder_id' => $fresh->id,
                    'user_id' => $user->id,
                ]);

                $delivery->update([
                    'status' => PracticeReminderDeliveryStatus::Failed,
                    'failure_reason_code' => 'delivery_exception',
                    'processed_at' => now(),
                ]);

                $this->advanceReminder($fresh, $dueInstant);

                return 'failed';
            }

            // 5. Advance reminder to next occurrence
            $this->advanceReminder($fresh, $dueInstant);

            return 'sent';
        });
    }

    /**
     * Advance the reminder to its next scheduled occurrence or expire if one-time.
     */
    public function advanceReminder(PracticeReminder $reminder, Carbon $previousDue): void
    {
        $reminder->last_scheduled_at_utc = $previousDue;

        if ($reminder->schedule_type === PracticeReminderScheduleType::Once) {
            $reminder->is_enabled = false;
            $reminder->next_due_at_utc = null;
        } else {
            $nextDue = $this->scheduleService->calculateNextDue($reminder, $previousDue);
            if ($nextDue && $nextDue->lessThanOrEqualTo(now())) {
                $nextDue = $this->scheduleService->calculateNextDue($reminder, now());
            }
            $reminder->next_due_at_utc = $nextDue;
        }

        $reminder->save();
    }
}
