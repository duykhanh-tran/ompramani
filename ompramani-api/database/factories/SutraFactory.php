<?php

namespace Database\Factories;

use App\Models\Sutra;
use App\Models\Tradition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sutra>
 */
class SutraFactory extends Factory
{
    protected $model = Sutra::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'tradition_id' => Tradition::factory(),
            'title' => 'Kinh '.ucfirst($title),
            'slug' => Str::slug('Kinh '.$title),
            'original_title' => ucfirst(fake()->words(2, true)).' Sutra',
            'short_description' => fake()->paragraph(),
            'thumbnail' => fake()->imageUrl(),
            'status' => 'published',
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function published(?\DateTimeInterface $publishedAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => $publishedAt ?? now(),
        ]);
    }

    public function futurePublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now()->addDays(7),
        ]);
    }
}
