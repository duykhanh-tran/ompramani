<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\PrayerResponseModerationStatus;
use App\Models\PrayerResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PrayerResponse
 */
class MyPrayerResponseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isOwner = $request->user()?->id === $this->user_id;

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'prayer_intention_public_id' => $this->prayerIntention?->public_id,
            'prayer_intention_title' => $this->prayerIntention?->title,
            'body' => $this->body,
            'moderation_status' => $this->moderation_status?->value,
            'moderation_status_label' => $this->moderation_status?->label(),
            'submission_revision' => $this->submission_revision,
            'rejection_reason_code' => $this->rejection_reason_code,
            'user_rejection_note' => $this->user_rejection_note,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'withdrawn_at' => $this->withdrawn_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'can_edit' => $isOwner && ! $this->trashed(),
            'can_withdraw' => $isOwner && $this->withdrawn_at === null,
            'can_delete' => $isOwner,
            'can_resubmit' => $isOwner && $this->moderation_status === PrayerResponseModerationStatus::Rejected,
        ];
    }
}
