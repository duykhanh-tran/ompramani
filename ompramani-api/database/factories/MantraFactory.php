<?php

namespace Database\Factories;

use App\Models\BuddhistFigure;
use App\Models\Mantra;
use App\Models\Tradition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Mantra>
 */
class MantraFactory extends Factory
{
    protected $model = Mantra::class;

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
            'buddhist_figure_id' => BuddhistFigure::factory(),
            'title' => 'Chú '.ucfirst($title),
            'slug' => Str::slug('Chú '.$title).'-'.fake()->unique()->randomNumber(4),
            'original_title' => ucfirst($title).' Dhāraṇī',
            'short_description' => fake()->paragraph(),
            'original_text' => 'Oṃ Maṇi Padme Hūṃ',
            'vietnamese_transcription' => 'Úm ma ni bát mê hồng',
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
}
