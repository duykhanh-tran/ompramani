<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerVisibility;
use App\Models\PrayerIntention;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PrayerIntention
 */
class MyPrayerIntentionResource extends JsonResource
{
    /**
     * Transform the resource for authenticated owner management.
     * Never exposes internal admin notes or moderator details.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isPublished = $this->isPublicApproved();

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'title' => $this->title,
            'body' => $this->body,
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'visibility' => $this->visibility->value,
            'visibility_label' => $this->visibility->label(),
            'moderation_status' => $this->moderation_status->value,
            'moderation_status_label' => $this->moderation_status->label(),
            'rejection_reason_code' => $this->rejection_reason_code,
            'user_rejection_note' => $this->user_rejection_note,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'withdrawn_at' => $this->withdrawn_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'public_url' => $isPublished ? "/cau-nguyen/{$this->public_id}" : null,
            'can_submit' => ($this->visibility === PrayerVisibility::Private || $this->moderation_status === PrayerModerationStatus::Rejected),
            'can_withdraw' => ($this->visibility === PrayerVisibility::Public && $this->withdrawn_at === null),
            'can_edit' => true,
            'can_delete' => true,
        ];
    }
}
