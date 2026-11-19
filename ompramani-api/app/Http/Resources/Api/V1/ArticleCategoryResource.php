<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ArticleCategory
 */
class ArticleCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'position' => $this->position,
            'published_articles_count' => $this->whenCounted('publishedArticles'),
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
