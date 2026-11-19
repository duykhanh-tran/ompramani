<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\TempleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TempleSearchRequest;
use App\Http\Resources\Api\V1\TempleDetailResource;
use App\Http\Resources\Api\V1\TempleSummaryResource;
use App\Models\Temple;
use App\Models\Tradition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class TempleController extends Controller
{
    /**
     * Get static registry of place types.
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'data' => TempleType::toRegistry(),
        ]);
    }

    /**
     * Get available public filter facets (types, provinces, countries, traditions).
     */
    public function filters(): JsonResponse
    {
        $facets = Cache::remember('temple_filter_facets', 3600, function () {
            $publishedQuery = Temple::query()->published();

            $provinces = (clone $publishedQuery)
                ->whereNotNull('province_name')
                ->where('province_name', '!=', '')
                ->distinct()
                ->orderBy('province_name', 'asc')
                ->pluck('province_name')
                ->values()
                ->all();

            $countries = (clone $publishedQuery)
                ->whereNotNull('country_code')
                ->where('country_code', '!=', '')
                ->distinct()
                ->orderBy('country_code', 'asc')
                ->pluck('country_code')
                ->values()
                ->all();

            $traditions = Tradition::query()
                ->active()
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'slug'])
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                ])
                ->values()
                ->all();

            return [
                'types' => TempleType::toRegistry(),
                'provinces' => $provinces,
                'countries' => $countries,
                'traditions' => $traditions,
            ];
        });

        return response()->json([
            'data' => $facets,
        ]);
    }

    /**
     * List and search published temples with deterministic sorting and verified filters.
     */
    public function index(TempleSearchRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 12);
        $placeType = $validated['type'] ?? $validated['place_type'] ?? null;
        $q = $validated['q'] ?? null;
        $sort = $validated['sort'] ?? null;

        $query = Temple::query()
            ->published()
            ->with(['traditions', 'coverMedia', 'media']);

        // Search keyword if provided
        if (filled($q)) {
            $query->search($q);
        }

        // Filters
        if (! empty($placeType)) {
            $query->byPlaceType($placeType);
        }

        if (! empty($validated['country'])) {
            $query->byCountry($validated['country']);
        }

        if (! empty($validated['province'])) {
            $query->byProvince($validated['province']);
        }

        if (! empty($validated['tradition'])) {
            $query->byTradition($validated['tradition']);
        }

        // Deterministic sorting
        if ($sort === 'relevance' || (empty($sort) && filled($q))) {
            $query->orderByRelevance($q);
        } elseif ($sort === 'name_desc') {
            $query->orderBy('name', 'desc')->orderBy('id', 'desc');
        } elseif ($sort === 'newest') {
            $query->orderBy('published_at', 'desc')->orderBy('id', 'desc');
        } else {
            // Default: name_asc
            $query->orderBy('name', 'asc')->orderBy('id', 'asc');
        }

        $temples = $query->paginate($perPage);

        return TempleSummaryResource::collection($temples);
    }

    /**
     * Get published temple detail by slug.
     */
    public function show(string $slug): TempleDetailResource
    {
        $cacheKey = "temple_detail_{$slug}";

        $temple = Cache::remember($cacheKey, 3600, function () use ($slug) {
            return Temple::query()
                ->published()
                ->where('slug', $slug)
                ->with(['traditions', 'sources', 'media', 'coverMedia', 'visitNotesSource'])
                ->first();
        });

        if (! $temple) {
            abort(404, 'Không tìm thấy địa điểm Phật giáo hoặc chưa được xuất bản.');
        }

        return new TempleDetailResource($temple);
    }
}
