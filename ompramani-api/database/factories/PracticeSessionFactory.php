<?php

namespace Database\Factories;

use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PracticeSession>
 */
class PracticeSessionFactory extends Factory
{
    protected $model = PracticeSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Active,
            'subject_type' => null,
            'subject_id' => null,
            'started_at' => now(),
            'ended_at' => null,
            'active_duration_seconds' => 0,
            'last_heartbeat_at' => now(),
            'last_activity_at' => now(),
            'progress_data' => ['planned_duration_seconds' => 900],
            'client_session_key' => (string) Str::uuid(),
            'sequence' => 0,
        ];
    }

    /**
     * Indicate that the session is completed.
     */
    public function completed(int $duration = 900): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PracticeSessionStatus::Completed,
            'ended_at' => now(),
            'active_duration_seconds' => $duration,
            'last_heartbeat_at' => null,
        ]);
    }

    /**
     * Indicate that the session is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PracticeSessionStatus::Paused,
            'last_heartbeat_at' => null,
        ]);
    }

    /**
     * Indicate that the session is for Mala practice.
     */
    public function mala(int $beadCount = 108): static
    {
        return $this->state(fn (array $attributes) => [
            'practice_type' => PracticeType::Mala,
            'progress_data' => ['bead_count' => $beadCount],
        ]);
    }
}
