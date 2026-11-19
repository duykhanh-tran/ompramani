<?php

namespace Database\Factories;

use App\Models\ArticleTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ArticleTag>
 */
class ArticleTagFactory extends Factory
{
    protected $model = ArticleTag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
        ];
    }
}
