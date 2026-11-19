<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'author_id' => User::factory(),
            'article_category_id' => ArticleCategory::factory(),
            'title' => ucfirst($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'excerpt' => fake()->paragraph(),
            'content' => '<p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>',
            'cover_image' => 'covers/'.fake()->word().'.jpg',
            'status' => 'published',
            'published_at' => now(),
            'meta_title' => ucfirst($title),
            'meta_description' => fake()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function review(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'review',
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
