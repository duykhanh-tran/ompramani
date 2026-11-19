<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Article
 */
class ArticleDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $relatedContent = $this->getPublishedRelatedBuddhistContent();
        $relatedArticles = $this->getRelatedArticles(4)->map(fn (Article $art) => [
            'id' => $art->id,
            'title' => $art->title,
            'slug' => $art->slug,
            'excerpt' => $art->excerpt ?: Str::limit(strip_tags($art->content), 180),
            'cover_image' => $art->cover_image,
            'published_at' => $art->published_at?->toISOString(),
        ])->all();

        $plainContent = strip_tags($this->content ?? '');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt ?: Str::limit($plainContent, 180),
            'content' => $this->content,
            'cover_image' => $this->cover_image,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'tags' => ArticleTagResource::collection($this->whenLoaded('tags')),
            'author' => $this->author ? [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ] : null,
            'published_at' => $this->published_at?->toISOString(),
            'meta_title' => $this->meta_title ?: $this->title,
            'meta_description' => $this->meta_description ?: ($this->excerpt ?: Str::limit($plainContent, 160)),
            'related_content' => $relatedContent,
            'related_articles' => $relatedArticles,
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
