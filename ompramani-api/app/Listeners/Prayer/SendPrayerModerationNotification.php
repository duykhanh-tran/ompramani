<?php

declare(strict_types=1);

namespace App\Listeners\Prayer;

use App\Events\Prayer\PrayerIntentionApproved;
use App\Events\Prayer\PrayerIntentionRejected;
use App\Events\Prayer\PrayerIntentionRevoked;
use App\Events\Prayer\PrayerResponseApproved;
use App\Events\Prayer\PrayerResponseRejected;
use App\Events\Prayer\PrayerResponseRevoked;
use App\Services\Notification\PrayerNotificationService;
use Illuminate\Support\Facades\Log;

class SendPrayerModerationNotification
{
    public function __construct(
        protected PrayerNotificationService $notificationService
    ) {}

    public function handle(object $event): void
    {
        try {
            if ($event instanceof PrayerIntentionApproved) {
                $this->notificationService->notifyPrayerIntentionApproved($event->intention);
            } elseif ($event instanceof PrayerIntentionRejected) {
                $this->notificationService->notifyPrayerIntentionRejected($event->intention, $event->userNote);
            } elseif ($event instanceof PrayerIntentionRevoked) {
                $this->notificationService->notifyPrayerIntentionRevoked($event->intention);
            } elseif ($event instanceof PrayerResponseApproved) {
                $this->notificationService->notifyPrayerResponseApproved($event->response);
            } elseif ($event instanceof PrayerResponseRejected) {
                $this->notificationService->notifyPrayerResponseRejected($event->response, $event->userNote);
            } elseif ($event instanceof PrayerResponseRevoked) {
                $this->notificationService->notifyPrayerResponseRevoked($event->response);
            }
        } catch (\Throwable $e) {
            // Safe logging: Never let notification failure rollback an approval or crash the caller
            Log::error('Failed to deliver prayer community notification: '.$e->getMessage(), [
                'event' => get_class($event),
                'exception' => $e,
            ]);
        }
    }
}
