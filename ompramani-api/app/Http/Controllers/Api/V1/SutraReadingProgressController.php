<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SutraReadingProgressResource;
use App\Models\Sutra;
use App\Models\SutraReadingProgress;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SutraReadingProgressController extends Controller
{
    /**
     * List current user's reading progresses.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $perPage = min((int) $request->input('per_page', 12), 50);

        $progresses = SutraReadingProgress::query()
            ->forUser($user->id)
            ->whereHas('sutra', function ($query): void {
                $query->published();
            })
            ->with(['sutra.tradition', 'version', 'section'])
            ->recent()
            ->paginate($perPage);

        return SutraReadingProgressResource::collection($progresses);
    }

    /**
     * Show reading progress for a specific sutra.
     */
    public function show(Request $request, string $slug): JsonResponse|SutraReadingProgressResource
    {
        $user = $request->user();

        $sutra = Sutra::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $versionId = $request->input('version_id');

        $query = SutraReadingProgress::query()
            ->forUser($user->id)
            ->where('sutra_id', $sutra->id)
            ->with(['sutra.tradition', 'version', 'section']);

        if ($versionId) {
            $query->where('sutra_version_id', (int) $versionId);
        }

        $progress = $query->recent()->first();

        if (! $progress) {
            return response()->json([
                'data' => null,
                'message' => 'Chưa có tiến trình đọc cho bộ kinh này.',
            ], 404);
        }

        return new SutraReadingProgressResource($progress);
    }

    /**
     * Upsert reading progress for a specific sutra and version.
     */
    public function upsert(Request $request, string $slug): JsonResponse|SutraReadingProgressResource
    {
        $user = $request->user();

        $sutra = Sutra::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $sutra) {
            abort(404, 'Bộ kinh không tồn tại hoặc chưa được xuất bản.');
        }

        $validated = $request->validate([
            'sutra_version_id' => 'nullable|integer',
            'sutra_section_id' => 'required|integer',
            'revision' => 'nullable|integer',
        ], [
            'sutra_section_id.required' => 'Vui lòng cung cấp ID phẩm kinh.',
        ]);

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

            $matchAttributes = [
                'user_id' => $user->id,
                'sutra_version_id' => $version->id,
            ];
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

            $matchAttributes = [
                'user_id' => $user->id,
                'sutra_id' => $sutra->id,
            ];
            $versionId = $section->sutra_version_id ?? $sutra->primaryVersion()?->id;
        }

        // Concurrency & Revision Check
        $existing = SutraReadingProgress::query()
            ->where($matchAttributes)
            ->first();

        if ($existing && isset($validated['revision'])) {
            $clientRevision = (int) $validated['revision'];
            if ($clientRevision < $existing->revision) {
                abort(409, 'Cập nhật tiến trình bị từ chối do phiên bản (revision) nhỏ hơn phiên bản hiện tại trên máy chủ.');
            }
        }

        $nextRevision = $existing ? ($existing->revision + 1) : 1;

        $progress = SutraReadingProgress::updateOrCreate(
            $matchAttributes,
            [
                'sutra_id' => $sutra->id,
                'sutra_version_id' => $versionId,
                'sutra_section_id' => $section->id,
                'last_read_at' => now(),
                'revision' => $nextRevision,
            ]
        );

        $progress->load(['sutra.tradition', 'version', 'section']);

        return (new SutraReadingProgressResource($progress))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Delete reading progress for a specific sutra.
     */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();

        $sutra = Sutra::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $versionId = $request->input('version_id');

        $query = SutraReadingProgress::query()
            ->where('user_id', $user->id)
            ->where('sutra_id', $sutra->id);

        if ($versionId) {
            $query->where('sutra_version_id', (int) $versionId);
        }

        $deletedCount = $query->delete();

        return response()->json([
            'message' => 'Đã xóa tiến trình đọc thành công.',
            'deleted_count' => $deletedCount,
        ]);
    }
}
