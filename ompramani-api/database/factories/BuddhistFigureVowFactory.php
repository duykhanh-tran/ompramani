<?php

namespace Database\Factories;

use App\Models\BuddhistFigure;
use App\Models\BuddhistFigureVow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuddhistFigureVow>
 */
class BuddhistFigureVowFactory extends Factory
{
    protected $model = BuddhistFigureVow::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buddhist_figure_id' => BuddhistFigure::factory(),
            'title' => 'Đại nguyện: '.fake()->sentence(4),
            'content' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
