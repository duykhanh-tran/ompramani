<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'dharma_name' => fake()->optional()->firstName(),
            'date_of_birth' => fake()->optional()->date('Y-m-d', '-18 years'),
            'gender' => fake()->optional()->randomElement(['male', 'female', 'other', 'prefer_not_to_say']),
            'avatar' => null,
            'preferred_tradition_id' => null,
            'location' => fake()->optional()->city(),
            'bio' => fake()->optional()->paragraph(2),
            'dharma_journey_started_at' => fake()->optional()->date('Y-m-d', '-5 years'),
        ];
    }
}
