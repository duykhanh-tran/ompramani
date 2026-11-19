<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListBuddhistFiguresRequest;
use App\Http\Resources\Api\V1\BuddhistFigureResource;
use App\Models\BuddhistFigure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BuddhistFigureController extends Controller
{
    /**
     * Display a listing of published Buddhist figures.
     */
    public function index(ListBuddhistFiguresRequest $request): AnonymousResourceCollection
    {
        $query = BuddhistFigure::query()
            ->published()
            ->with(['tradition', 'traditions', 'publishedAudios']);

        if ($request->filled('type')) {
            $query->where('figure_type', (string) $request->input('type'));
        }

        if ($request->filled('tradition')) {
            $traditionSlug = (string) $request->input('tradition');
            $query->where(function (Builder $q) use ($traditionSlug): void {
                $q->whereHas('tradition', function (Builder $tq) use ($traditionSlug): void {
                    $tq->where('slug', $traditionSlug);
                })->orWhereHas('traditions', function (Builder $tq) use ($traditionSlug): void {
                    $tq->where('slug', $traditionSlug);
                });
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $normalizedSearch = str_replace('-', ' ', $search);
            $slugSearch = Str::slug($search);
            $isPgsql = DB::connection()->getDriverName() === 'pgsql';
            $like = $isPgsql ? 'ilike' : 'like';

            $query->where(function (Builder $q) use ($search, $normalizedSearch, $slugSearch, $like): void {
                $q->where('name', $like, "%{$search}%")
                    ->orWhere('name', $like, "%{$normalizedSearch}%")
                    ->orWhere('original_name', $like, "%{$search}%")
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

        $perPage = (int) $request->input('per_page', 12);
        $figures = $query->latest('published_at')->latest('id')->paginate($perPage);

        return BuddhistFigureResource::collection($figures);
    }

    /**
     * Display the specified published Buddhist figure by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $figure = BuddhistFigure::query()
            ->published()
            ->where('slug', $slug)
            ->with([
                'tradition',
                'traditions',
                'publishedAudios',
                'vows',
                'sutras' => function ($q): void {
                    $q->published()->latest('id');
                },
                'mantras' => function ($q): void {
                    $q->published()->with(['tradition'])->latest('id');
                },
            ])
            ->first();

        if (! $figure) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return response()->json([
            'data' => new BuddhistFigureResource($figure),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
