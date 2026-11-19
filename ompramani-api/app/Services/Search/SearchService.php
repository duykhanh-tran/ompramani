<?php

namespace App\Services\Search;

use App\Models\Article;
use App\Models\BuddhistFigure;
use App\Models\Mantra;
use App\Models\SearchQueryLog;
use App\Models\Sutra;
use App\Models\User;
use App\Support\Search\FeatureRegistry;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    /**
     * Perform public search across published sutras, figures, mantras, articles, and features.
     *
     * @param  array{
     *     q: string,
     *     type?: string,
     *     tradition?: ?string,
     *     page?: int,
     *     per_page?: int,
     *     sort?: string
     * }  $params
     * @return array{data: array<int, mixed>, meta: array{query: string, total: int, page: int, per_page: int}}
     */
    public function search(array $params, ?User $user = null): array
    {
        $rawQuery = trim((string) ($params['q'] ?? ''));
        $norm = SearchNormalizer::normalize($rawQuery);
        $type = (string) ($params['type'] ?? 'all');
        $tradition = ! empty($params['tradition']) ? (string) $params['tradition'] : null;
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($params['per_page'] ?? 12)));
        $sort = (string) ($params['sort'] ?? 'relevance');

        if ($norm === '') {
            return [
                'data' => [],
                'meta' => [
                    'query' => $rawQuery,
                    'total' => 0,
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ];
        }

        // 1. Search matching features from registry
        $matchingFeatures = [];
        if (in_array($type, ['all', 'feature'], true)) {
            $matchingFeatures = FeatureRegistry::search($rawQuery);
        }

        // 2. If type is only 'feature', return feature results paginated
        if ($type === 'feature') {
            $totalFeatures = count($matchingFeatures);
            $offset = ($page - 1) * $perPage;
            $slicedFeatures = array_slice($matchingFeatures, $offset, $perPage);

            $this->logQuery($rawQuery, $norm, $totalFeatures, $user);

            return [
                'data' => array_values($slicedFeatures),
                'meta' => [
                    'query' => $rawQuery,
                    'total' => $totalFeatures,
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ];
        }

        // 3. Search by specific type
        if ($type === 'sutra') {
            $sutrasQuery = $this->buildSutrasQuery($norm, $tradition, $sort);
            $paginator = $sutrasQuery->paginate($perPage, ['*'], 'page', $page);
            $items = [];
            foreach ($paginator->items() as $sutra) {
                $items[] = $this->formatSutraItem($sutra, $norm);
            }

            $this->logQuery($rawQuery, $norm, $paginator->total(), $user);

            return [
                'data' => $items,
                'meta' => [
                    'query' => $rawQuery,
                    'total' => $paginator->total(),
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ];
        }

        if ($type === 'figure') {
            $figuresQuery = $this->buildFiguresQuery($norm, $tradition, $sort);
            $paginator = $figuresQuery->paginate($perPage, ['*'], 'page', $page);
            $items = [];
            foreach ($paginator->items() as $figure) {
                $items[] = $this->formatFigureItem($figure, $norm);
            }

            $this->logQuery($rawQuery, $norm, $paginator->total(), $user);

            return [
                'data' => $items,
                'meta' => [
                    'query' => $rawQuery,
                    'total' => $paginator->total(),
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ];
        }

        if ($type === 'mantra') {
            $mantrasQuery = $this->buildMantrasQuery($norm, $tradition, $sort);
            $paginator = $mantrasQuery->paginate($perPage, ['*'], 'page', $page);
            $items = [];
            foreach ($paginator->items() as $mantra) {
                $items[] = $this->formatMantraItem($mantra, $norm);
            }

            $this->logQuery($rawQuery, $norm, $paginator->total(), $user);

            return [
                'data' => $items,
                'meta' => [
                    'query' => $rawQuery,
                    'total' => $paginator->total(),
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ];
        }

        if ($type === 'article') {
            $articlesQuery = $this->buildArticlesQuery($norm, $sort);
            $paginator = $articlesQuery->paginate($perPage, ['*'], 'page', $page);
            $items = [];
            foreach ($paginator->items() as $article) {
                $items[] = $this->formatArticleItem($article, $norm);
            }

            $this->logQuery($rawQuery, $norm, $paginator->total(), $user);

            return [
                'data' => $items,
                'meta' => [
                    'query' => $rawQuery,
                    'total' => $paginator->total(),
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ];
        }

        // 4. Combined 'all' search
        $sutrasQuery = $this->buildSutrasQuery($norm, $tradition, $sort);
        $figuresQuery = $this->buildFiguresQuery($norm, $tradition, $sort);
        $mantrasQuery = $this->buildMantrasQuery($norm, $tradition, $sort);
        $articlesQuery = $this->buildArticlesQuery($norm, $sort);

        $totalSutras = (clone $sutrasQuery)->count();
        $totalFigures = (clone $figuresQuery)->count();
        $totalMantras = (clone $mantrasQuery)->count();
        $totalArticles = (clone $articlesQuery)->count();

        $maxFetch = min(150, $page * $perPage);
        $sutraItems = (clone $sutrasQuery)->take($maxFetch)->get();
        $figureItems = (clone $figuresQuery)->take($maxFetch)->get();
        $mantraItems = (clone $mantrasQuery)->take($maxFetch)->get();
        $articleItems = (clone $articlesQuery)->take($maxFetch)->get();

        $allRanked = collect();

        foreach ($sutraItems as $sutra) {
            $allRanked->push([
                'data' => $this->formatSutraItem($sutra, $norm),
                'score' => (int) ($sutra->relevance_score ?? 0),
                'published_at' => $sutra->published_at?->timestamp ?? 0,
                'id' => $sutra->id,
            ]);
        }

        foreach ($figureItems as $figure) {
            $allRanked->push([
                'data' => $this->formatFigureItem($figure, $norm),
                'score' => (int) ($figure->relevance_score ?? 0),
                'published_at' => $figure->published_at?->timestamp ?? 0,
                'id' => $figure->id,
            ]);
        }

        foreach ($mantraItems as $mantra) {
            $allRanked->push([
                'data' => $this->formatMantraItem($mantra, $norm),
                'score' => (int) ($mantra->relevance_score ?? 0),
                'published_at' => $mantra->published_at?->timestamp ?? 0,
                'id' => $mantra->id,
            ]);
        }

        foreach ($articleItems as $article) {
            $allRanked->push([
                'data' => $this->formatArticleItem($article, $norm),
                'score' => (int) ($article->relevance_score ?? 0),
                'published_at' => $article->published_at?->timestamp ?? 0,
                'id' => $article->id,
            ]);
        }

        if ($sort === 'newest') {
            $sorted = $allRanked->sort(function ($a, $b) {
                if ($a['published_at'] === $b['published_at']) {
                    return $b['id'] <=> $a['id'];
                }

                return $b['published_at'] <=> $a['published_at'];
            });
        } else {
            $sorted = $allRanked->sort(function ($a, $b) {
                if ($a['score'] === $b['score']) {
                    return $a['id'] <=> $b['id'];
                }

                return $b['score'] <=> $a['score'];
            });
        }

        $offset = ($page - 1) * $perPage;
        $pagedRecords = $sorted->slice($offset, $perPage)->pluck('data')->values()->all();

        $totalResults = $totalSutras + $totalFigures + $totalMantras + $totalArticles;
        $combinedData = $pagedRecords;

        if (! empty($matchingFeatures)) {
            $totalResults += count($matchingFeatures);
            if ($page === 1) {
                $combinedData = array_merge($matchingFeatures, $pagedRecords);
            }
        }

        $this->logQuery($rawQuery, $norm, $totalResults, $user);

        return [
            'data' => $combinedData,
            'meta' => [
                'query' => $rawQuery,
                'total' => $totalResults,
                'page' => $page,
                'per_page' => $perPage,
            ],
        ];
    }

    /**
     * Fast suggestions / autocomplete for titles, aliases, figures, mantras, articles, and features.
     *
     * @return array<int, array{type: string, title: string, slug?: string, url?: string}>
     */
    public function suggest(string $query, int $limit = 10): array
    {
        $rawQuery = trim($query);
        $norm = SearchNormalizer::normalize($rawQuery);

        if (mb_strlen($norm, 'UTF-8') < 2) {
            return [];
        }

        $normContains = "%{$norm}%";
        $results = [];

        // 1. Suggest matching features
        $features = FeatureRegistry::search($rawQuery);
        foreach ($features as $f) {
            $results[] = [
                'type' => 'feature',
                'title' => $f['title'],
                'slug' => $f['slug'],
                'url' => $f['url'],
            ];
            if (count($results) >= $limit) {
                return $results;
            }
        }

        $remaining = $limit - count($results);

        // 2. Suggest published figures
        if ($remaining > 0) {
            $figures = BuddhistFigure::query()
                ->published()
                ->where(function (Builder $w) use ($norm, $normContains): void {
                    $w->whereRaw('unaccent(LOWER(buddhist_figures.name)) LIKE ?', [$normContains])
                        ->orWhereRaw('unaccent(LOWER(COALESCE(buddhist_figures.original_name, \'\'))) LIKE ?', [$normContains])
                        ->orWhereHas('searchAliases', function (Builder $aq) use ($norm, $normContains): void {
                            $aq->where('normalized_alias', $norm)
                                ->orWhere('normalized_alias', 'LIKE', $normContains);
                        });
                })
                ->orderByRaw('CASE WHEN unaccent(LOWER(buddhist_figures.name)) = ? THEN 100 WHEN unaccent(LOWER(buddhist_figures.name)) LIKE ? THEN 50 ELSE 10 END DESC', [$norm, "{$norm}%"])
                ->limit($remaining)
                ->get(['id', 'name', 'slug']);

            foreach ($figures as $fig) {
                $results[] = [
                    'type' => 'figure',
                    'title' => $fig->name,
                    'slug' => $fig->slug,
                ];
            }
        }

        $remaining = $limit - count($results);

        // 3. Suggest published mantras
        if ($remaining > 0) {
            $mantras = Mantra::query()
                ->published()
                ->where(function (Builder $w) use ($norm, $normContains): void {
                    $w->whereRaw('unaccent(LOWER(mantras.title)) LIKE ?', [$normContains])
                        ->orWhereRaw('unaccent(LOWER(COALESCE(mantras.original_title, \'\'))) LIKE ?', [$normContains])
                        ->orWhereHas('searchAliases', function (Builder $aq) use ($norm, $normContains): void {
                            $aq->where('normalized_alias', $norm)
                                ->orWhere('normalized_alias', 'LIKE', $normContains);
                        });
                })
                ->orderByRaw('CASE WHEN unaccent(LOWER(mantras.title)) = ? THEN 100 WHEN unaccent(LOWER(mantras.title)) LIKE ? THEN 50 ELSE 10 END DESC', [$norm, "{$norm}%"])
                ->limit($remaining)
                ->get(['id', 'title', 'slug']);

            foreach ($mantras as $m) {
                $results[] = [
                    'type' => 'mantra',
                    'title' => $m->title,
                    'slug' => $m->slug,
                ];
            }
        }

        $remaining = $limit - count($results);

        // 4. Suggest published articles
        if ($remaining > 0) {
            $articles = Article::query()
                ->published()
                ->where(function (Builder $w) use ($norm, $normContains): void {
                    $w->whereRaw('unaccent(LOWER(articles.title)) LIKE ?', [$normContains])
                        ->orWhereHas('searchAliases', function (Builder $aq) use ($norm, $normContains): void {
                            $aq->where('normalized_alias', $norm)
                                ->orWhere('normalized_alias', 'LIKE', $normContains);
                        });
                })
                ->orderByRaw('CASE WHEN unaccent(LOWER(articles.title)) = ? THEN 100 WHEN unaccent(LOWER(articles.title)) LIKE ? THEN 50 ELSE 10 END DESC', [$norm, "{$norm}%"])
                ->limit($remaining)
                ->get(['id', 'title', 'slug']);

            foreach ($articles as $art) {
                $results[] = [
                    'type' => 'article',
                    'title' => $art->title,
                    'slug' => $art->slug,
                ];
            }
        }

        $remaining = $limit - count($results);

        // 5. Suggest published sutras by title or alias
        if ($remaining > 0) {
            $sutras = Sutra::query()
                ->published()
                ->where(function (Builder $w) use ($norm, $normContains): void {
                    $w->whereRaw('unaccent(LOWER(sutras.title)) LIKE ?', [$normContains])
                        ->orWhereRaw('unaccent(LOWER(COALESCE(sutras.original_title, \'\'))) LIKE ?', [$normContains])
                        ->orWhereHas('searchAliases', function (Builder $aq) use ($norm, $normContains): void {
                            $aq->where('normalized_alias', $norm)
                                ->orWhere('normalized_alias', 'LIKE', $normContains);
                        });
                })
                ->orderByRaw('CASE WHEN unaccent(LOWER(sutras.title)) = ? THEN 100 WHEN unaccent(LOWER(sutras.title)) LIKE ? THEN 50 ELSE 10 END DESC', [$norm, "{$norm}%"])
                ->limit($remaining)
                ->get(['id', 'title', 'slug']);

            foreach ($sutras as $s) {
                $results[] = [
                    'type' => 'sutra',
                    'title' => $s->title,
                    'slug' => $s->slug,
                ];
            }
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * Build the Sutras search query with relevance ranking.
     */
    protected function buildSutrasQuery(string $norm, ?string $traditionSlug, string $sort): Builder
    {
        $normPrefix = "{$norm}%";
        $normContains = "%{$norm}%";

        $scoreSql = "
        (
            CASE WHEN unaccent(LOWER(sutras.title)) = ? THEN 100 ELSE 0 END +
            CASE WHEN unaccent(LOWER(sutras.title)) LIKE ? THEN 60 ELSE 0 END +
            CASE WHEN unaccent(LOWER(sutras.title)) LIKE ? THEN 40 ELSE 0 END +
            CASE WHEN unaccent(LOWER(COALESCE(sutras.original_title, ''))) LIKE ? THEN 30 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM search_aliases 
                WHERE (searchable_type = 'sutra' OR searchable_type = 'App\\\\Models\\\\Sutra') 
                  AND searchable_id = sutras.id 
                  AND normalized_alias = ?
            ) THEN 25 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM search_aliases 
                WHERE (searchable_type = 'sutra' OR searchable_type = 'App\\\\Models\\\\Sutra') 
                  AND searchable_id = sutras.id 
                  AND normalized_alias LIKE ?
            ) THEN 20 ELSE 0 END +
            CASE WHEN unaccent(LOWER(COALESCE(sutras.short_description, ''))) LIKE ? THEN 15 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM sutra_sections 
                JOIN sutra_versions ON sutra_sections.sutra_version_id = sutra_versions.id 
                WHERE sutra_versions.sutra_id = sutras.id 
                  AND sutra_versions.status = 'published' 
                  AND unaccent(LOWER(sutra_sections.content)) LIKE ?
            ) THEN 5 ELSE 0 END
        ) AS relevance_score";

        $scoreBindings = [
            $norm,
            $normPrefix,
            $normContains,
            $normContains,
            $norm,
            $normContains,
            $normContains,
            $normContains,
        ];

        $query = Sutra::query()
            ->published()
            ->with(['tradition', 'searchAliases'])
            ->select('sutras.*')
            ->selectRaw($scoreSql, $scoreBindings)
            ->where(function (Builder $w) use ($norm, $normContains): void {
                $w->whereRaw('unaccent(LOWER(sutras.title)) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(COALESCE(sutras.original_title, \'\'))) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(COALESCE(sutras.short_description, \'\'))) LIKE ?', [$normContains])
                    ->orWhereHas('searchAliases', function (Builder $aq) use ($norm, $normContains): void {
                        $aq->where('normalized_alias', $norm)
                            ->orWhere('normalized_alias', 'LIKE', $normContains)
                            ->orWhere('alias', 'LIKE', $normContains);
                    })
                    ->orWhereHas('versions', function (Builder $vq) use ($normContains): void {
                        $vq->where('status', 'published')
                            ->whereHas('allSections', function (Builder $sq) use ($normContains): void {
                                $sq->whereRaw('unaccent(LOWER(sutra_sections.content)) LIKE ?', [$normContains]);
                            });
                    });
            });

        if ($traditionSlug !== null) {
            $query->whereHas('tradition', function (Builder $tq) use ($traditionSlug): void {
                $tq->where('slug', $traditionSlug);
            });
        }

        if ($sort === 'newest') {
            $query->orderByDesc('sutras.published_at')->orderByDesc('sutras.id');
        } else {
            $query->orderByDesc('relevance_score')->orderBy('sutras.id');
        }

        return $query;
    }

    /**
     * Build the BuddhistFigures search query with relevance ranking.
     */
    protected function buildFiguresQuery(string $norm, ?string $traditionSlug, string $sort): Builder
    {
        $normPrefix = "{$norm}%";
        $normContains = "%{$norm}%";

        $scoreSql = "
        (
            CASE WHEN unaccent(LOWER(buddhist_figures.name)) = ? THEN 100 ELSE 0 END +
            CASE WHEN unaccent(LOWER(buddhist_figures.name)) LIKE ? THEN 60 ELSE 0 END +
            CASE WHEN unaccent(LOWER(buddhist_figures.name)) LIKE ? THEN 40 ELSE 0 END +
            CASE WHEN unaccent(LOWER(COALESCE(buddhist_figures.original_name, ''))) LIKE ? THEN 30 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM search_aliases 
                WHERE (searchable_type = 'figure' OR searchable_type = 'App\\\\Models\\\\BuddhistFigure') 
                  AND searchable_id = buddhist_figures.id 
                  AND normalized_alias = ?
            ) THEN 25 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM search_aliases 
                WHERE (searchable_type = 'figure' OR searchable_type = 'App\\\\Models\\\\BuddhistFigure') 
                  AND searchable_id = buddhist_figures.id 
                  AND normalized_alias LIKE ?
            ) THEN 20 ELSE 0 END +
            CASE WHEN unaccent(LOWER(COALESCE(buddhist_figures.short_description, ''))) LIKE ? THEN 15 ELSE 0 END
        ) AS relevance_score";

        $scoreBindings = [
            $norm,
            $normPrefix,
            $normContains,
            $normContains,
            $norm,
            $normContains,
            $normContains,
        ];

        $query = BuddhistFigure::query()
            ->published()
            ->with(['tradition', 'traditions', 'searchAliases'])
            ->select('buddhist_figures.*')
            ->selectRaw($scoreSql, $scoreBindings)
            ->where(function (Builder $w) use ($norm, $normContains): void {
                $w->whereRaw('unaccent(LOWER(buddhist_figures.name)) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(COALESCE(buddhist_figures.original_name, \'\'))) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(COALESCE(buddhist_figures.short_description, \'\'))) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(COALESCE(buddhist_figures.biography, \'\'))) LIKE ?', [$normContains])
                    ->orWhereHas('searchAliases', function (Builder $aq) use ($norm, $normContains): void {
                        $aq->where('normalized_alias', $norm)
                            ->orWhere('normalized_alias', 'LIKE', $normContains)
                            ->orWhere('alias', 'LIKE', $normContains);
                    });
            });

        if ($traditionSlug !== null) {
            $query->where(function (Builder $tq) use ($traditionSlug): void {
                $tq->whereHas('tradition', function (Builder $sub) use ($traditionSlug): void {
                    $sub->where('slug', $traditionSlug);
                })->orWhereHas('traditions', function (Builder $sub) use ($traditionSlug): void {
                    $sub->where('slug', $traditionSlug);
                });
            });
        }

        if ($sort === 'newest') {
            $query->orderByDesc('buddhist_figures.published_at')->orderByDesc('buddhist_figures.id');
        } else {
            $query->orderByDesc('relevance_score')->orderBy('buddhist_figures.id');
        }

        return $query;
    }

    /**
     * Build the Mantras search query with relevance ranking.
     */
    protected function buildMantrasQuery(string $norm, ?string $traditionSlug, string $sort): Builder
    {
        $normPrefix = "{$norm}%";
        $normContains = "%{$norm}%";

        $scoreSql = "
        (
            CASE WHEN unaccent(LOWER(mantras.title)) = ? THEN 100 ELSE 0 END +
            CASE WHEN unaccent(LOWER(mantras.title)) LIKE ? THEN 60 ELSE 0 END +
            CASE WHEN unaccent(LOWER(mantras.title)) LIKE ? THEN 40 ELSE 0 END +
            CASE WHEN unaccent(LOWER(COALESCE(mantras.original_title, ''))) LIKE ? THEN 30 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM search_aliases 
                WHERE (searchable_type = 'mantra' OR searchable_type = 'App\\\\Models\\\\Mantra') 
                  AND searchable_id = mantras.id 
                  AND normalized_alias = ?
            ) THEN 25 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM search_aliases 
                WHERE (searchable_type = 'mantra' OR searchable_type = 'App\\\\Models\\\\Mantra') 
                  AND searchable_id = mantras.id 
                  AND normalized_alias LIKE ?
            ) THEN 20 ELSE 0 END +
            CASE WHEN unaccent(LOWER(COALESCE(mantras.short_description, ''))) LIKE ? THEN 15 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM mantra_versions 
                WHERE mantra_versions.mantra_id = mantras.id 
                  AND mantra_versions.status = 'published' 
                  AND unaccent(LOWER(COALESCE(mantra_versions.transliteration, ''))) LIKE ?
            ) THEN 8 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM mantra_versions 
                WHERE mantra_versions.mantra_id = mantras.id 
                  AND mantra_versions.status = 'published' 
                  AND unaccent(LOWER(mantra_versions.content)) LIKE ?
            ) THEN 5 ELSE 0 END
        ) AS relevance_score";

        $scoreBindings = [
            $norm,
            $normPrefix,
            $normContains,
            $normContains,
            $norm,
            $normContains,
            $normContains,
            $normContains,
            $normContains,
        ];

        $query = Mantra::query()
            ->published()
            ->with(['tradition', 'figure', 'searchAliases'])
            ->select('mantras.*')
            ->selectRaw($scoreSql, $scoreBindings)
            ->where(function (Builder $w) use ($norm, $normContains): void {
                $w->whereRaw('unaccent(LOWER(mantras.title)) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(COALESCE(mantras.original_title, \'\'))) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(COALESCE(mantras.short_description, \'\'))) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(COALESCE(mantras.original_text, \'\'))) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(COALESCE(mantras.vietnamese_transcription, \'\'))) LIKE ?', [$normContains])
                    ->orWhereHas('searchAliases', function (Builder $aq) use ($norm, $normContains): void {
                        $aq->where('normalized_alias', $norm)
                            ->orWhere('normalized_alias', 'LIKE', $normContains)
                            ->orWhere('alias', 'LIKE', $normContains);
                    })
                    ->orWhereHas('versions', function (Builder $vq) use ($normContains): void {
                        $vq->where('status', 'published')
                            ->where(function (Builder $q) use ($normContains): void {
                                $q->whereRaw('unaccent(LOWER(content)) LIKE ?', [$normContains])
                                    ->orWhereRaw('unaccent(LOWER(COALESCE(transliteration, \'\'))) LIKE ?', [$normContains]);
                            });
                    });
            });

        if ($traditionSlug !== null) {
            $query->whereHas('tradition', function (Builder $tq) use ($traditionSlug): void {
                $tq->where('slug', $traditionSlug);
            });
        }

        if ($sort === 'newest') {
            $query->orderByDesc('mantras.published_at')->orderByDesc('mantras.id');
        } else {
            $query->orderByDesc('relevance_score')->orderBy('mantras.id');
        }

        return $query;
    }

    /**
     * Build the Articles search query with relevance ranking.
     */
    protected function buildArticlesQuery(string $norm, string $sort): Builder
    {
        $normPrefix = "{$norm}%";
        $normContains = "%{$norm}%";

        $scoreSql = "
        (
            CASE WHEN unaccent(LOWER(articles.title)) = ? THEN 100 ELSE 0 END +
            CASE WHEN unaccent(LOWER(articles.title)) LIKE ? THEN 60 ELSE 0 END +
            CASE WHEN unaccent(LOWER(articles.title)) LIKE ? THEN 40 ELSE 0 END +
            CASE WHEN unaccent(LOWER(COALESCE(articles.excerpt, ''))) LIKE ? THEN 25 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM search_aliases 
                WHERE (searchable_type = 'article' OR searchable_type = 'App\\\\Models\\\\Article') 
                  AND searchable_id = articles.id 
                  AND normalized_alias = ?
            ) THEN 25 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM search_aliases 
                WHERE (searchable_type = 'article' OR searchable_type = 'App\\\\Models\\\\Article') 
                  AND searchable_id = articles.id 
                  AND normalized_alias LIKE ?
            ) THEN 20 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM article_article_tag 
                JOIN article_tags ON article_article_tag.article_tag_id = article_tags.id 
                WHERE article_article_tag.article_id = articles.id 
                  AND (unaccent(LOWER(article_tags.name)) LIKE ? OR article_tags.slug LIKE ?)
            ) THEN 20 ELSE 0 END +
            CASE WHEN EXISTS (
                SELECT 1 FROM article_categories 
                WHERE article_categories.id = articles.article_category_id 
                  AND unaccent(LOWER(article_categories.name)) LIKE ?
            ) THEN 15 ELSE 0 END +
            CASE WHEN unaccent(LOWER(articles.content)) LIKE ? THEN 5 ELSE 0 END
        ) AS relevance_score";

        $scoreBindings = [
            $norm,
            $normPrefix,
            $normContains,
            $normContains,
            $norm,
            $normContains,
            $normContains,
            $normContains,
            $normContains,
            $normContains,
        ];

        $query = Article::query()
            ->published()
            ->with(['category', 'tags', 'searchAliases'])
            ->select('articles.*')
            ->selectRaw($scoreSql, $scoreBindings)
            ->where(function (Builder $w) use ($norm, $normContains): void {
                $w->whereRaw('unaccent(LOWER(articles.title)) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(COALESCE(articles.excerpt, \'\'))) LIKE ?', [$normContains])
                    ->orWhereRaw('unaccent(LOWER(articles.content)) LIKE ?', [$normContains])
                    ->orWhereHas('searchAliases', function (Builder $aq) use ($norm, $normContains): void {
                        $aq->where('normalized_alias', $norm)
                            ->orWhere('normalized_alias', 'LIKE', $normContains)
                            ->orWhere('alias', 'LIKE', $normContains);
                    })
                    ->orWhereHas('tags', function (Builder $tq) use ($normContains): void {
                        $tq->whereRaw('unaccent(LOWER(article_tags.name)) LIKE ?', [$normContains])
                            ->orWhere('article_tags.slug', 'LIKE', $normContains);
                    })
                    ->orWhereHas('category', function (Builder $cq) use ($normContains): void {
                        $cq->whereRaw('unaccent(LOWER(article_categories.name)) LIKE ?', [$normContains]);
                    });
            });

        if ($sort === 'newest') {
            $query->orderByDesc('articles.published_at')->orderByDesc('articles.id');
        } else {
            $query->orderByDesc('relevance_score')->orderBy('articles.id');
        }

        return $query;
    }

    /**
     * Format a Sutra search item.
     *
     * @return array<string, mixed>
     */
    protected function formatSutraItem(Sutra $sutra, string $norm): array
    {
        return [
            'type' => 'sutra',
            'id' => $sutra->id,
            'title' => $sutra->title,
            'slug' => $sutra->slug,
            'original_title' => $sutra->original_title,
            'description' => $sutra->short_description,
            'thumbnail' => $sutra->thumbnail,
            'tradition' => $sutra->tradition ? [
                'id' => $sutra->tradition->id,
                'name' => $sutra->tradition->name,
                'slug' => $sutra->tradition->slug,
            ] : null,
            'matched_in' => $this->determineMatchedInForSutra($sutra, $norm),
        ];
    }

    /**
     * Format a BuddhistFigure search item.
     *
     * @return array<string, mixed>
     */
    protected function formatFigureItem(BuddhistFigure $figure, string $norm): array
    {
        return [
            'type' => 'figure',
            'id' => $figure->id,
            'title' => $figure->name,
            'name' => $figure->name,
            'slug' => $figure->slug,
            'original_name' => $figure->original_name,
            'figure_type' => $figure->figure_type,
            'description' => $figure->short_description,
            'thumbnail' => $figure->thumbnail,
            'tradition' => $figure->tradition ? [
                'id' => $figure->tradition->id,
                'name' => $figure->tradition->name,
                'slug' => $figure->tradition->slug,
            ] : null,
            'matched_in' => $this->determineMatchedInForFigure($figure, $norm),
        ];
    }

    /**
     * Format a Mantra search item.
     *
     * @return array<string, mixed>
     */
    protected function formatMantraItem(Mantra $mantra, string $norm): array
    {
        $thumbnail = $mantra->figure?->thumbnail ?: $mantra->thumbnail;

        return [
            'type' => 'mantra',
            'id' => $mantra->id,
            'title' => $mantra->title,
            'slug' => $mantra->slug,
            'original_title' => $mantra->original_title,
            'description' => $mantra->short_description,
            'thumbnail' => $thumbnail,
            'tradition' => $mantra->tradition ? [
                'id' => $mantra->tradition->id,
                'name' => $mantra->tradition->name,
                'slug' => $mantra->tradition->slug,
            ] : null,
            'figure' => $mantra->figure ? [
                'id' => $mantra->figure->id,
                'name' => $mantra->figure->name,
                'slug' => $mantra->figure->slug,
            ] : null,
            'matched_in' => $this->determineMatchedInForMantra($mantra, $norm),
        ];
    }

    /**
     * Format an Article search item.
     *
     * @return array<string, mixed>
     */
    protected function formatArticleItem(Article $article, string $norm): array
    {
        return [
            'type' => 'article',
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'description' => $article->excerpt,
            'thumbnail' => $article->cover_image,
            'category' => $article->category ? [
                'id' => $article->category->id,
                'name' => $article->category->name,
                'slug' => $article->category->slug,
            ] : null,
            'matched_in' => $this->determineMatchedInForArticle($article, $norm),
        ];
    }

    /**
     * Determine which fields matched the query for a Sutra.
     *
     * @return array<int, string>
     */
    protected function determineMatchedInForSutra(Sutra $sutra, string $norm): array
    {
        $matched = [];

        $titleNorm = SearchNormalizer::normalize($sutra->title);
        $originalTitleNorm = SearchNormalizer::normalize($sutra->original_title);

        if (str_contains($titleNorm, $norm) || ($originalTitleNorm !== '' && str_contains($originalTitleNorm, $norm))) {
            $matched[] = 'title';
        }

        if ($sutra->relationLoaded('searchAliases')) {
            foreach ($sutra->searchAliases as $alias) {
                if (str_contains($alias->normalized_alias, $norm) || str_contains(SearchNormalizer::normalize($alias->alias), $norm)) {
                    $matched[] = 'alias';
                    break;
                }
            }
        }

        $descNorm = SearchNormalizer::normalize($sutra->short_description);
        if ($descNorm !== '' && str_contains($descNorm, $norm)) {
            $matched[] = 'description';
        }

        $contentMatched = $sutra->versions()
            ->where('status', 'published')
            ->whereHas('allSections', function (Builder $sq) use ($norm): void {
                $sq->whereRaw('unaccent(LOWER(content)) LIKE ?', ["%{$norm}%"]);
            })
            ->exists();

        if ($contentMatched) {
            $matched[] = 'content';
        }

        return empty($matched) ? ['title'] : array_values(array_unique($matched));
    }

    /**
     * Determine which fields matched the query for a Buddhist Figure.
     *
     * @return array<int, string>
     */
    protected function determineMatchedInForFigure(BuddhistFigure $figure, string $norm): array
    {
        $matched = [];

        $nameNorm = SearchNormalizer::normalize($figure->name);
        $originalNameNorm = SearchNormalizer::normalize($figure->original_name);

        if (str_contains($nameNorm, $norm) || ($originalNameNorm !== '' && str_contains($originalNameNorm, $norm))) {
            $matched[] = 'title';
        }

        if ($figure->relationLoaded('searchAliases')) {
            foreach ($figure->searchAliases as $alias) {
                if (str_contains($alias->normalized_alias, $norm) || str_contains(SearchNormalizer::normalize($alias->alias), $norm)) {
                    $matched[] = 'alias';
                    break;
                }
            }
        }

        $descNorm = SearchNormalizer::normalize($figure->short_description);
        if ($descNorm !== '' && str_contains($descNorm, $norm)) {
            $matched[] = 'description';
        }

        $bioNorm = SearchNormalizer::normalize($figure->biography);
        if ($bioNorm !== '' && str_contains($bioNorm, $norm)) {
            $matched[] = 'content';
        }

        return empty($matched) ? ['title'] : array_values(array_unique($matched));
    }

    /**
     * Determine which fields matched the query for a Mantra.
     *
     * @return array<int, string>
     */
    protected function determineMatchedInForMantra(Mantra $mantra, string $norm): array
    {
        $matched = [];

        $titleNorm = SearchNormalizer::normalize($mantra->title);
        $originalTitleNorm = SearchNormalizer::normalize($mantra->original_title);

        if (str_contains($titleNorm, $norm) || ($originalTitleNorm !== '' && str_contains($originalTitleNorm, $norm))) {
            $matched[] = 'title';
        }

        if ($mantra->relationLoaded('searchAliases')) {
            foreach ($mantra->searchAliases as $alias) {
                if (str_contains($alias->normalized_alias, $norm) || str_contains(SearchNormalizer::normalize($alias->alias), $norm)) {
                    $matched[] = 'alias';
                    break;
                }
            }
        }

        $descNorm = SearchNormalizer::normalize($mantra->short_description);
        if ($descNorm !== '' && str_contains($descNorm, $norm)) {
            $matched[] = 'description';
        }

        if (!empty($mantra->original_text)) {
            $origNorm = SearchNormalizer::normalize($mantra->original_text);
            if ($origNorm !== '' && str_contains($origNorm, $norm)) {
                $matched[] = 'content';
            }
        }

        if (!empty($mantra->vietnamese_transcription)) {
            $vietNorm = SearchNormalizer::normalize($mantra->vietnamese_transcription);
            if ($vietNorm !== '' && str_contains($vietNorm, $norm)) {
                $matched[] = 'transliteration';
            }
        }

        $versions = $mantra->versions()->where('status', 'published')->get();
        foreach ($versions as $v) {
            $transNorm = SearchNormalizer::normalize($v->transliteration);
            if ($transNorm !== '' && str_contains($transNorm, $norm)) {
                $matched[] = 'transliteration';
            }

            $contentNorm = SearchNormalizer::normalize($v->content);
            if ($contentNorm !== '' && str_contains($contentNorm, $norm)) {
                $matched[] = 'content';
            }
        }

        return empty($matched) ? ['title'] : array_values(array_unique($matched));
    }

    /**
     * Determine which fields matched the query for an Article.
     *
     * @return array<int, string>
     */
    protected function determineMatchedInForArticle(Article $article, string $norm): array
    {
        $matched = [];

        $titleNorm = SearchNormalizer::normalize($article->title);
        if (str_contains($titleNorm, $norm)) {
            $matched[] = 'title';
        }

        if ($article->relationLoaded('searchAliases')) {
            foreach ($article->searchAliases as $alias) {
                if (str_contains($alias->normalized_alias, $norm) || str_contains(SearchNormalizer::normalize($alias->alias), $norm)) {
                    $matched[] = 'alias';
                    break;
                }
            }
        }

        $excerptNorm = SearchNormalizer::normalize($article->excerpt);
        if ($excerptNorm !== '' && str_contains($excerptNorm, $norm)) {
            $matched[] = 'excerpt';
        }

        if ($article->relationLoaded('tags')) {
            foreach ($article->tags as $tag) {
                if (str_contains(SearchNormalizer::normalize($tag->name), $norm) || str_contains($tag->slug, $norm)) {
                    $matched[] = 'tag';
                    break;
                }
            }
        }

        if ($article->relationLoaded('category') && $article->category) {
            if (str_contains(SearchNormalizer::normalize($article->category->name), $norm)) {
                $matched[] = 'category';
            }
        }

        $contentNorm = SearchNormalizer::normalize(strip_tags($article->content));
        if ($contentNorm !== '' && str_contains($contentNorm, $norm)) {
            $matched[] = 'content';
        }

        return empty($matched) ? ['title'] : array_values(array_unique($matched));
    }

    /**
     * Non-blocking query logger.
     */
    protected function logQuery(string $query, string $normalizedQuery, int $resultCount, ?User $user = null): void
    {
        try {
            SearchQueryLog::create([
                'query' => $query,
                'normalized_query' => $normalizedQuery,
                'result_count' => $resultCount,
                'user_id' => $user?->id,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Silently continue if log fails
        }
    }
}
