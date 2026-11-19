<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SutraVersionResource;
use App\Models\Sutra;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class SutraVersionController extends Controller
{
    /**
     * Display published versions of a sutra.
     */
    public function index(string $slug): JsonResponse
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

        $versions = $sutra->versions()
            ->where('status', 'published')
            ->orderBy('is_default', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'data' => SutraVersionResource::collection($versions),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Display the table of contents (sections) for a sutra version.
     */
    public function sections(int $id): JsonResponse
    {
        $version = SutraVersion::query()
            ->with('sutra')
            ->find($id);

        if (! $version || ! $version->isPublished()) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $sutra = $version->sutra;
        if (! $sutra || ! $sutra->isPublished()) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $sections = SutraSection::query()
            ->where('sutra_version_id', $version->id)
            ->select(['id', 'sutra_version_id', 'parent_id', 'title', 'slug', 'position', 'source_page_start', 'source_page_end'])
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();

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
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'position' => $item->position,
                    'parent_id' => $item->parent_id,
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
