<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListArticlesRequest;
use App\Http\Resources\Api\V1\ArticleDetailResource;
use App\Http\Resources\Api\V1\ArticleListResource;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    /**
     * Display a listing of published articles.
     */
    public function index(ListArticlesRequest $request): AnonymousResourceCollection
    {
        $query = Article::query()
            ->published()
            ->with(['category', 'tags', 'author']);

        if ($request->filled('category')) {
            $categorySlug = (string) $request->input('category');
            $query->whereHas('category', function (Builder $q) use ($categorySlug): void {
                $q->where('slug', $categorySlug);
            });
        }

        if ($request->filled('tag')) {
            $tagSlug = (string) $request->input('tag');
            $query->whereHas('tags', function (Builder $q) use ($tagSlug): void {
                $q->where('slug', $tagSlug);
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $normalizedSearch = str_replace('-', ' ', $search);
            $slugSearch = Str::slug($search);
            $isPgsql = DB::connection()->getDriverName() === 'pgsql';
            $like = $isPgsql ? 'ilike' : 'like';

            $query->where(function (Builder $q) use ($search, $normalizedSearch, $slugSearch, $like): void {
                $q->where('title', $like, "%{$search}%")
                    ->orWhere('title', $like, "%{$normalizedSearch}%")
                    ->orWhere('excerpt', $like, "%{$search}%")
                    ->orWhere('slug', $like, "%{$search}%")
                    ->orWhere('slug', 'like', "%{$slugSearch}%")
                    ->orWhereHas('searchAliases', function (Builder $aq) use ($search, $normalizedSearch, $slugSearch, $like): void {
                        $norm = str_replace('-', ' ', $slugSearch);
                        $aq->where('alias', $like, "%{$search}%")
                            ->orWhere('alias', $like, "%{$normalizedSearch}%")
                            ->orWhere('normalized_alias', 'like', "%{$norm}%");
                    });
            });
        }

        $sort = (string) $request->input('sort', 'newest');
        if ($sort === 'oldest') {
            $query->oldest('published_at')->oldest('id');
        } else {
            $query->latest('published_at')->latest('id');
        }

        $perPage = min(50, max(1, (int) $request->input('per_page', 12)));
        $articles = $query->paginate($perPage);

        return ArticleListResource::collection($articles);
    }

    /**
     * Display the specified published article by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->with(['category', 'tags', 'author'])
            ->first();

        if (! $article) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return response()->json([
            'data' => new ArticleDetailResource($article),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
