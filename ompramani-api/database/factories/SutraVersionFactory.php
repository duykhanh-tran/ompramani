<?php

namespace Database\Factories;

use App\Models\Sutra;
use App\Models\SutraVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SutraVersion>
 */
class SutraVersionFactory extends Factory
{
    protected $model = SutraVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sutra_id' => Sutra::factory(),
            'name' => 'Bản dịch '.fake()->name(),
            'translator' => fake()->name(),
            'language' => 'vi',
            'publisher' => fake()->company(),
            'publication_year' => (string) fake()->year(),
            'source' => fake()->url(),
            'notes' => fake()->sentence(),
            'is_default' => false,
            'status' => 'published',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
