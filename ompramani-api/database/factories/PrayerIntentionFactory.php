<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PrayerCategory;
use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerVisibility;
use App\Models\PrayerIntention;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PrayerIntention>
 */
class PrayerIntentionFactory extends Factory
{
    protected $model = PrayerIntention::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(3).' Nguyện cho tất cả chúng sinh luôn được an vui, giải thoát.',
            'category' => fake()->randomElement(PrayerCategory::cases()),
            'visibility' => PrayerVisibility::Private,
            'moderation_status' => PrayerModerationStatus::NotRequired,
            'submitted_at' => null,
            'approved_at' => null,
            'published_at' => null,
            'withdrawn_at' => null,
            'rejected_at' => null,
            'moderated_by' => null,
            'rejection_reason_code' => null,
            'user_rejection_note' => null,
            'admin_notes' => null,
        ];
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => PrayerVisibility::Private,
            'moderation_status' => PrayerModerationStatus::NotRequired,
            'submitted_at' => null,
            'approved_at' => null,
            'published_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Pending,
            'submitted_at' => now(),
            'approved_at' => null,
            'published_at' => null,
            'withdrawn_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'submitted_at' => now()->subHours(2),
            'approved_at' => now()->subHour(),
            'published_at' => now()->subHour(),
            'withdrawn_at' => null,
        ]);
    }

    public function rejected(?string $reasonCode = 'personal_information'): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Rejected,
            'submitted_at' => now()->subHours(3),
            'rejected_at' => now()->subHour(),
            'rejection_reason_code' => $reasonCode,
            'user_rejection_note' => 'Vui lòng ẩn thông tin cá nhân trước khi gửi chia sẻ cộng đồng.',
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => PrayerVisibility::Private,
            'moderation_status' => PrayerModerationStatus::NotRequired,
            'submitted_at' => now()->subDays(2),
            'approved_at' => now()->subDays(1),
            'published_at' => now()->subDays(1),
            'withdrawn_at' => now()->subHour(),
        ]);
    }
}
