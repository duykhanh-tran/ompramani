<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PrayerResponseModerationStatus;
use App\Models\PrayerIntention;
use App\Models\PrayerResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PrayerResponse>
 */
class PrayerResponseFactory extends Factory
{
    protected $model = PrayerResponse::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'prayer_intention_id' => PrayerIntention::factory()->approved(),
            'user_id' => User::factory(),
            'body' => 'Nguyện mười phương chư Phật gia hộ cho bạn cùng gia quyến luôn được vạn sự bình an, thân tâm thanh tịnh.',
            'moderation_status' => PrayerResponseModerationStatus::Pending,
            'submission_revision' => 1,
            'parent_publication_revision' => now(),
            'submitted_at' => now(),
            'approved_at' => null,
            'published_at' => null,
            'withdrawn_at' => null,
            'rejected_at' => null,
            'revoked_at' => null,
            'moderated_by' => null,
            'rejection_reason_code' => null,
            'user_rejection_note' => null,
            'admin_notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => PrayerResponseModerationStatus::Pending,
            'submitted_at' => now(),
            'approved_at' => null,
            'published_at' => null,
            'withdrawn_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => PrayerResponseModerationStatus::Approved,
            'submitted_at' => now()->subHours(2),
            'approved_at' => now()->subHour(),
            'published_at' => now()->subHour(),
            'withdrawn_at' => null,
        ]);
    }

    public function rejected(?string $reason = 'personal_information'): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => PrayerResponseModerationStatus::Rejected,
            'submitted_at' => now()->subHours(2),
            'rejected_at' => now()->subHour(),
            'rejection_reason_code' => $reason,
            'user_rejection_note' => 'Vui lòng không đưa thông tin cá nhân vào lời nhắn đồng hành.',
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => PrayerResponseModerationStatus::Revoked,
            'submitted_at' => now()->subDays(2),
            'approved_at' => now()->subDays(1),
            'published_at' => now()->subDays(1),
            'revoked_at' => now()->subHour(),
            'rejection_reason_code' => 'spam',
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => PrayerResponseModerationStatus::Approved,
            'submitted_at' => now()->subDays(2),
            'approved_at' => now()->subDays(1),
            'published_at' => now()->subDays(1),
            'withdrawn_at' => now()->subHour(),
        ]);
    }
}
