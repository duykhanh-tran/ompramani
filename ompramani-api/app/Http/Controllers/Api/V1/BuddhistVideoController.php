<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\VideoCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VideoSearchRequest;
use App\Http\Resources\Api\V1\VideoDetailResource;
use App\Http\Resources\Api\V1\VideoSummaryResource;
use App\Models\BuddhistVideo;
use App\Models\VideoTopic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class BuddhistVideoController extends Controller
{
    /**
     * Get list of video categories.
     */
    public function categories(): JsonResponse
    {
        $categories = Cache::remember('video_categories_registry', 3600, function () {
            return VideoCategory::toRegistry();
        });

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Get active topics for video filtering.
     */
    public function topics(): JsonResponse
    {
        $topics = Cache::remember('video_topics_list', 3600, function () {
            return VideoTopic::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'description']);
        });

        return response()->json([
            'data' => $topics,
        ]);
    }

    /**
     * List and search published videos.
     */
    public function index(VideoSearchRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 12);
        $q = $validated['q'] ?? null;
        $category = $validated['category'] ?? null;
        $topic = $validated['topic'] ?? null;
        $speaker = $validated['speaker'] ?? null;
        $sort = $validated['sort'] ?? null;

        $query = BuddhistVideo::query()
            ->published()
            ->with('topics');

        if (filled($q)) {
            $query->search($q);
        }

        if (filled($category)) {
            $query->byCategory($category);
        }

        if (filled($topic)) {
            $query->byTopic($topic);
        }

        if (filled($speaker)) {
            $query->bySpeaker($speaker);
        }

        // Sorting
        if ($sort === 'oldest') {
            $query->orderBy('published_at', 'asc')->orderBy('id', 'asc');
        } elseif ($sort === 'duration_desc') {
            $query->orderByRaw('duration_seconds DESC NULLS LAST')->orderBy('id', 'desc');
        } elseif ($sort === 'duration_asc') {
            $query->orderByRaw('duration_seconds ASC NULLS LAST')->orderBy('id', 'asc');
        } else {
            // Default: newest
            $query->orderBy('published_at', 'desc')->orderBy('id', 'desc');
        }

        $videos = $query->paginate($perPage);

        return VideoSummaryResource::collection($videos);
    }

    /**
     * Get video detail by slug.
     */
    public function show(string $slug): VideoDetailResource
    {
        $cacheKey = "video_detail_{$slug}";

        $video = Cache::remember($cacheKey, 3600, function () use ($slug) {
            return BuddhistVideo::query()
                ->published()
                ->where('slug', $slug)
                ->with('topics')
                ->first();
        });

        if (! $video) {
            abort(404, 'Không tìm thấy video Phật pháp hoặc chưa được xuất bản.');
        }

        return new VideoDetailResource($video);
    }
}
