<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SutraSectionDetailResource;
use App\Models\SutraSection;
use Illuminate\Http\JsonResponse;

class SutraSectionController extends Controller
{
    /**
     * Display a specific section's content.
     */
    public function show(int $id): JsonResponse
    {
        $section = SutraSection::query()
            ->with(['sutra', 'version.sutra'])
            ->find($id);

        if (! $section) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // If section is tied to a version, that version must be published
        if ($section->version && ! $section->version->isPublished()) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $sutra = $section->sutra ?? $section->version?->sutra;
        if (! $sutra || ! $sutra->isPublished()) {
            return response()->json([
                'message' => 'Không tìm thấy nội dung.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Verify parent-child integrity
        $current = $section;
        $visited = [$current->id => true];

        while ($current->parent_id !== null) {
            $parent = SutraSection::query()->find($current->parent_id);

            // Parent does not exist or belongs to another sutra or forms a cycle
            if (! $parent || ($parent->sutra_id && $parent->sutra_id !== $sutra->id) || isset($visited[$parent->id])) {
                return response()->json([
                    'message' => 'Không tìm thấy nội dung.',
                ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $visited[$parent->id] = true;
            $current = $parent;
        }

        return response()->json([
            'data' => new SutraSectionDetailResource($section),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
