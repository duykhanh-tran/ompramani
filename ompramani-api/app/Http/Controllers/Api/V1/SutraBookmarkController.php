<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SutraBookmarkResource;
use App\Models\Sutra;
use App\Models\SutraBookmark;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SutraBookmarkController extends Controller
{
    /**
     * List user bookmarks with optional type filter.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $perPage = min((int) $request->input('per_page', 12), 50);
        $type = $request->input('type');

        $query = SutraBookmark::query()
            ->forUser($user->id)
            ->whereHas('sutra', function ($q): void {
                $q->published();
            })
            ->with(['sutra.tradition', 'version', 'section'])
            ->orderByDesc('created_at');

        if ($type === 'sutra') {
            $query->sutras();
        } elseif ($type === 'section') {
            $query->sections()->whereHas('version', function ($q): void {
                $q->published();
            });
        }

        return SutraBookmarkResource::collection($query->paginate($perPage));
    }

    /**
     * Check bookmarks status for a specific sutra.
     */
    public function check(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();

        $sutra = Sutra::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $isSutraSaved = SutraBookmark::query()
            ->forUser($user->id)
            ->sutras()
            ->where('sutra_id', $sutra->id)
            ->exists();

        $bookmarkedSectionIds = SutraBookmark::query()
            ->forUser($user->id)
            ->sections()
            ->where('sutra_id', $sutra->id)
            ->pluck('sutra_section_id')
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'is_sutra_saved' => $isSutraSaved,
                'bookmarked_section_ids' => $bookmarkedSectionIds,
            ],
        ]);
    }

    /**
     * Store/toggle a bookmark (idempotent).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'required|in:sutra,section',
            'sutra_id' => 'required|integer',
            'sutra_version_id' => 'nullable|integer',
            'sutra_section_id' => 'required_if:type,section|nullable|integer',
        ], [
            'type.required' => 'Loại đánh dấu không được để trống.',
            'sutra_id.required' => 'Vui lòng cung cấp ID bộ kinh.',
        ]);

        $sutra = Sutra::query()
            ->published()
            ->where('id', $validated['sutra_id'])
            ->first();

        if (! $sutra) {
            abort(404, 'Bộ kinh không tồn tại hoặc chưa được xuất bản.');
        }

        if ($validated['type'] === 'sutra') {
            $bookmark = SutraBookmark::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'sutra',
                    'sutra_id' => $sutra->id,
                ],
                [
                    'sutra_version_id' => null,
                    'sutra_section_id' => null,
                ]
            );
        } else {
            if (! empty($validated['sutra_version_id'])) {
                $version = SutraVersion::query()
                    ->where('id', $validated['sutra_version_id'])
                    ->where('sutra_id', $sutra->id)
                    ->published()
                    ->first();

                if (! $version) {
                    abort(422, 'Phiên bản kinh không hợp lệ hoặc chưa được xuất bản.');
                }

                $section = SutraSection::query()
                    ->where('id', $validated['sutra_section_id'])
                    ->where('sutra_version_id', $version->id)
                    ->first();

                if (! $section) {
                    abort(422, 'Phẩm mục kinh không thuộc phiên bản được chọn.');
                }

                $versionId = $version->id;
            } else {
                $section = SutraSection::query()
                    ->where('id', $validated['sutra_section_id'])
                    ->where(function ($q) use ($sutra) {
                        $q->where('sutra_id', $sutra->id)
                            ->orWhereHas('version', function ($vq) use ($sutra) {
                                $vq->where('sutra_id', $sutra->id);
                            });
                    })
                    ->first();

                if (! $section) {
                    abort(422, 'Phẩm mục kinh không thuộc bộ kinh này.');
                }

                $versionId = $section->sutra_version_id ?? $sutra->primaryVersion()?->id;
            }

            $bookmark = SutraBookmark::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'section',
                    'sutra_section_id' => $section->id,
                ],
                [
                    'sutra_id' => $sutra->id,
                    'sutra_version_id' => $versionId,
                ]
            );
        }

        $bookmark->load(['sutra.tradition', 'version', 'section']);

        return (new SutraBookmarkResource($bookmark))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete a bookmark by ID.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $bookmark = SutraBookmark::query()
            ->forUser($user->id)
            ->where('id', $id)
            ->firstOrFail();

        $bookmark->delete();

        return response()->json([
            'message' => 'Đã xóa đánh dấu thành công.',
        ]);
    }

    /**
     * Remove bookmark by target (sutra_id or sutra_section_id).
     */
    public function removeByTarget(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'required|in:sutra,section',
            'sutra_id' => 'required_if:type,sutra|nullable|integer',
            'sutra_section_id' => 'required_if:type,section|nullable|integer',
        ]);

        $query = SutraBookmark::query()->forUser($user->id)->where('type', $validated['type']);

        if ($validated['type'] === 'sutra') {
            $query->where('sutra_id', (int) $validated['sutra_id']);
        } else {
            $query->where('sutra_section_id', (int) $validated['sutra_section_id']);
        }

        $deletedCount = $query->delete();

        return response()->json([
            'message' => 'Đã bỏ lưu / bỏ đánh dấu thành công.',
            'deleted_count' => $deletedCount,
        ]);
    }
}
