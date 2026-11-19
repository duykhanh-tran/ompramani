<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\PrayerNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PrayerCommunityNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public PrayerNotificationType $notificationType,
        public string $resourceType,
        public string $resourcePublicId,
        public array $metadata = []
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $template = $this->notificationType->defaultTemplate(array_merge($this->metadata, [
            'resource_public_id' => $this->resourcePublicId,
        ]));

        return [
            'type' => $this->notificationType->value,
            'category' => $this->notificationType->category(),
            'title' => $template['title'],
            'message' => $template['message'],
            'resource_type' => $this->resourceType,
            'resource_public_id' => $this->resourcePublicId,
            'destination' => $template['destination'],
            'idempotency_key' => $this->metadata['idempotency_key'] ?? null,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
