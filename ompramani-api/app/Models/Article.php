<?php

namespace App\Models;

use App\Models\Concerns\HasPublishedScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Article extends Model
{
    use HasFactory, HasPublishedScope;

    protected $fillable = [
        'author_id',
        'article_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ArticleTag::class, 'article_article_tag')->withTimestamps();
    }

    public function articleRelations(): HasMany
    {
        return $this->hasMany(ArticleRelation::class)->orderBy('position');
    }

    public function searchAliases(): MorphMany
    {
        return $this->morphMany(SearchAlias::class, 'searchable');
    }

    public function aliases(): MorphMany
    {
        return $this->searchAliases();
    }

    /**
     * Retrieve related Buddhist content (Sutra, Figure, Mantra) that is published.
     *
     * @return array<int, array{type: string, id: int, title: string, slug: string}>
     */
    public function getPublishedRelatedBuddhistContent(): array
    {
        $relations = $this->articleRelations()->with('related')->get();
        $items = [];

        foreach ($relations as $rel) {
            $model = $rel->related;
            if (! $model) {
                continue;
            }

            // Verify the related entity is published
            if (method_exists($model, 'isPublished') && ! $model->isPublished()) {
                continue;
            }

            $type = match ($rel->related_type) {
                'sutra', Sutra::class => 'sutra',
                'figure', BuddhistFigure::class => 'figure',
                'mantra', Mantra::class => 'mantra',
                default => $rel->related_type,
            };

            $title = match ($type) {
                'figure' => $model->name ?? '',
                default => $model->title ?? '',
            };

            $items[] = [
                'type' => $type,
                'id' => (int) $model->id,
                'title' => (string) $title,
                'slug' => (string) ($model->slug ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Get related published articles by category or tags, excluding current article.
     *
     * @return Collection<int, Article>
     */
    public function getRelatedArticles(int $limit = 4): Collection
    {
        $tagIds = $this->tags->pluck('id')->all();
        $categoryId = $this->article_category_id;

        return static::query()
            ->published()
            ->where('id', '!=', $this->id)
            ->where(function (Builder $q) use ($categoryId, $tagIds): void {
                if ($categoryId !== null) {
                    $q->where('article_category_id', $categoryId);
                }
                if (! empty($tagIds)) {
                    $q->orWhereHas('tags', function (Builder $tq) use ($tagIds): void {
                        $tq->whereIn('article_tags.id', $tagIds);
                    });
                }
            })
            ->latest('published_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
