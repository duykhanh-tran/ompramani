<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListSutrasRequest;
use App\Http\Resources\Api\V1\SutraResource;
use App\Models\Sutra;
use App\Models\SutraSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SutraController extends Controller
{
    /**
     * Display a listing of published sutras.
     */
    public function index(ListSutrasRequest $request): AnonymousResourceCollection
    {
        $query = Sutra::query()
            ->published()
            ->with('tradition');

        if ($request->filled('tradition')) {
            $traditionSlug = (string) $request->input('tradition');
            $query->whereHas('tradition', function ($q) use ($traditionSlug): void {
                $q->where('slug', $traditionSlug);
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $normalizedSearch = str_replace('-', ' ', $search);
            $slugSearch = Str::slug($search);
            $isPgsql = DB::connection()->getDriverName() === 'pgsql';
            $like = $isPgsql ? 'ilike' : 'like';

            $query->where(function ($q) use ($search, $normalizedSearch, $slugSearch, $like): void {
                $q->where('title', $like, "%{$search}%")
                    ->orWhere('title', $like, "%{$normalizedSearch}%")
                    ->orWhere('slug', $like, "%{$search}%")
                    ->orWhere('slug', 'like', "%{$slugSearch}%");
            });
        }

        $perPage = (int) $request->input('per_page', 12);
        $sutras = $query->latest('published_at')->latest('id')->paginate($perPage);

        return SutraResource::collection($sutras);
    }

    /**
     * Display the specified published sutra.
     */
    public function show(string $slug): JsonResponse
    {
        $sutra = Sutra::query()
            ->published()
            ->where('slug', $slug)
            ->with([
                'tradition',
                'versions' => function ($q): void {
                    $q->where('status', 'published')
                        ->orderBy('is_default', 'desc')
                        ->orderBy('id', 'asc');
                },
            ])
            ->first();

        if (! $sutra) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return response()->json([
            'data' => new SutraResource($sutra),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Display the table of contents (sections) for a sutra directly.
     */
    public function sections(string $slug): JsonResponse
    {
        $sutra = Sutra::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $sutra) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Query sections directly by sutra_id, or fallback to published version sections
        $sections = SutraSection::query()
            ->where('sutra_id', $sutra->id)
            ->select(['id', 'sutra_id', 'sutra_version_id', 'parent_id', 'title', 'slug', 'position', 'source_page_start', 'source_page_end'])
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($sections->isEmpty()) {
            $version = $sutra->primaryVersion();
            if ($version) {
                $sections = SutraSection::query()
                    ->where('sutra_version_id', $version->id)
                    ->select(['id', 'sutra_id', 'sutra_version_id', 'parent_id', 'title', 'slug', 'position', 'source_page_start', 'source_page_end'])
                    ->orderBy('position', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();
            }
        }

        $tree = $this->buildSectionTree($sections);

        return response()->json([
            'data' => $tree,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Build nested hierarchy tree without raw content, avoiding infinite loops.
     *
     * @param  Collection<int, SutraSection>  $sections
     * @return array<int, array<string, mixed>>
     */
    private function buildSectionTree(Collection $sections): array
    {
        $grouped = $sections->groupBy('parent_id');
        $visited = [];

        $buildBranch = function (?int $parentId) use (&$buildBranch, $grouped, &$visited): array {
            $branch = [];
            $items = $grouped->get($parentId, collect());

            foreach ($items as $item) {
                if (isset($visited[$item->id])) {
                    continue; // Prevent cycles
                }
                $visited[$item->id] = true;

                $branch[] = [
                    'id' => $item->id,
                    'sutra_id' => $item->sutra_id,
                    'sutra_version_id' => $item->sutra_version_id,
                    'parent_id' => $item->parent_id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'position' => $item->position,
                    'source_page_start' => $item->source_page_start,
                    'source_page_end' => $item->source_page_end,
                    'children' => $buildBranch($item->id),
                ];
            }

            return $branch;
        };

        return $buildBranch(null);
    }
}
