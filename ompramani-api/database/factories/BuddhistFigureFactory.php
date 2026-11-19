<?php

namespace Database\Factories;

use App\Models\BuddhistFigure;
use App\Models\Tradition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BuddhistFigure>
 */
class BuddhistFigureFactory extends Factory
{
    protected $model = BuddhistFigure::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'tradition_id' => Tradition::factory(),
            'name' => 'Đức '.$name,
            'slug' => Str::slug('Đức '.$name).'-'.fake()->unique()->randomNumber(4),
            'original_name' => $name,
            'figure_type' => fake()->randomElement(['buddha', 'bodhisattva', 'arahant', 'teacher']),
            'short_description' => fake()->paragraph(),
            'biography' => fake()->paragraphs(2, true),
            'vows_introduction' => fake()->paragraph(),
            'great_vows' => fake()->paragraph(),
            'iconography' => fake()->paragraph(),
            'practice_guidance' => fake()->paragraph(),
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

    public function buddha(): static
    {
        return $this->state(fn (array $attributes) => [
            'figure_type' => 'buddha',
        ]);
    }

    public function bodhisattva(): static
    {
        return $this->state(fn (array $attributes) => [
            'figure_type' => 'bodhisattva',
        ]);
    }
}
