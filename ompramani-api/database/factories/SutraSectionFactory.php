<?php

namespace Database\Factories;

use App\Models\SutraSection;
use App\Models\SutraVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SutraSection>
 */
class SutraSectionFactory extends Factory
{
    protected $model = SutraSection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'sutra_version_id' => SutraVersion::factory(),
            'parent_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => fake()->paragraphs(3, true),
            'position' => 1,
            'source_page_start' => 1,
            'source_page_end' => 10,
        ];
    }
}
