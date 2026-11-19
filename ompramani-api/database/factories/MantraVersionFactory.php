<?php

namespace Database\Factories;

use App\Models\Mantra;
use App\Models\MantraVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MantraVersion>
 */
class MantraVersionFactory extends Factory
{
    protected $model = MantraVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mantra_id' => Mantra::factory(),
            'name' => fake()->randomElement(['Phạn văn', 'Phiên âm Hán-Việt', 'Phiên âm Latin', 'Bản tiếng Việt tham khảo']),
            'language' => fake()->randomElement(['sa', 'pi', 'zh-Latn', 'vi']),
            'script' => fake()->randomElement(['latin', 'devanagari', 'siddham', 'han']),
            'content' => 'Oṃ Maṇi Padme Hūṃ. Tāre Tuttāre Ture Svāhā.',
            'transliteration' => 'Om Mani Padme Hum',
            'translation' => 'Viên ngọc quý sáng ngời trong hoa sen.',
            'source' => fake()->sentence(),
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
