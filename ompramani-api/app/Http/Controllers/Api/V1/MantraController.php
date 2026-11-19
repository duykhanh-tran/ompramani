<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListMantrasRequest;
use App\Http\Resources\Api\V1\MantraResource;
use App\Http\Resources\Api\V1\MantraVersionResource;
use App\Models\Mantra;
use App\Models\MantraVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MantraController extends Controller
{
    /**
     * Display a listing of published mantras.
     */
    public function index(ListMantrasRequest $request): AnonymousResourceCollection
    {
        $query = Mantra::query()
            ->published()
            ->with([
                'tradition',
                'figure' => function ($q): void {
                    $q->published();
                },
                'defaultVersion' => function ($q): void {
                    $q->where('status', 'published');
                },
            ]);

        if ($request->filled('tradition')) {
            $traditionSlug = (string) $request->input('tradition');
            $query->whereHas('tradition', function (Builder $q) use ($traditionSlug): void {
                $q->where('slug', $traditionSlug);
            });
        }

        if ($request->filled('figure')) {
            $figureParam = (string) $request->input('figure');
            $query->whereHas('figure', function (Builder $q) use ($figureParam): void {
                $q->published()->where(function (Builder $sub) use ($figureParam): void {
                    $sub->where('slug', $figureParam);
                    if (ctype_digit($figureParam)) {
                        $sub->orWhere('id', (int) $figureParam);
                    }
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
                $q->where('title', $like, "%{$search}%")
                    ->orWhere('title', $like, "%{$normalizedSearch}%")
                    ->orWhere('original_title', $like, "%{$search}%")
                    ->orWhere('original_text', $like, "%{$search}%")
                    ->orWhere('vietnamese_transcription', $like, "%{$search}%")
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
        $mantras = $query->latest('published_at')->latest('id')->paginate($perPage);

        return MantraResource::collection($mantras);
    }

    /**
     * Display the specified published mantra by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $mantra = Mantra::query()
            ->published()
            ->where('slug', $slug)
            ->with([
                'tradition',
                'figure' => function ($q): void {
                    $q->published();
                },
                'defaultVersion' => function ($q): void {
                    $q->where('status', 'published');
                },
                'versions' => function ($q): void {
                    $q->where('status', 'published')
                        ->with('publishedAudios')
                        ->orderBy('is_default', 'desc')
                        ->orderBy('id', 'asc');
                },
            ])
            ->first();

        if (! $mantra) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return response()->json([
            'data' => new MantraResource($mantra),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Display published versions of a mantra.
     */
    public function versions(string $slug): JsonResponse
    {
        $mantra = Mantra::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $mantra) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $versions = $mantra->versions()
            ->where('status', 'published')
            ->with('publishedAudios')
            ->orderBy('is_default', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'data' => MantraVersionResource::collection($versions),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Display a specific published mantra version.
     */
    public function versionDetail(int $id): JsonResponse
    {
        $version = MantraVersion::query()
            ->with(['mantra', 'publishedAudios'])
            ->find($id);

        if (! $version || ! $version->isPublished()) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $mantra = $version->mantra;
        if (! $mantra || ! $mantra->isPublished()) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return response()->json([
            'data' => new MantraVersionResource($version),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
