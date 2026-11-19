<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\VideoEditorialStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserFavoriteResource;
use App\Models\Article;
use App\Models\BuddhistVideo;
use App\Models\Sutra;
use App\Models\SutraBookmark;
use App\Models\UserFavorite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserFavoriteController extends Controller
{
    /**
     * List user favorites with optional content type filter and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $request->validate([
            'type' => 'nullable|string|in:all,sutra,article,video',
            'per_page' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
        ]);

        $type = $request->input('type');
        $perPage = (int) $request->input('per_page', 12);

        $query = UserFavorite::query()
            ->forUser($user->id)
            ->with(['favoritable'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($type && $type !== 'all') {
            $query->ofType($type);
        }

        return UserFavoriteResource::collection($query->paginate($perPage));
    }

    /**
     * Add or update favorite (idempotent PUT).
     */
    public function storeOrUpdate(Request $request, string $type, string $publicId): JsonResponse
    {
        $user = $request->user();

        if (! array_key_exists($type, UserFavorite::ALLOWED_TYPES)) {
            return response()->json([
                'message' => 'Loại nội dung yêu thích không được hỗ trợ.',
                'errors' => ['type' => ['Chỉ hỗ trợ: sutra, article, video.']],
            ], 422);
        }

        $target = $this->resolveTarget($type, $publicId);

        if (! $target || ! $this->isTargetPublished($target, $type)) {
            return response()->json([
                'message' => 'Nội dung không tồn tại hoặc chưa được xuất bản.',
            ], 404);
        }

        UserFavorite::firstOrCreate([
            'user_id' => $user->id,
            'favoritable_type' => $type,
            'favoritable_id' => $target->id,
        ]);

        // Keep Task 24 SutraBookmark (type='sutra') in sync without breaking bookmarks
        if ($type === 'sutra') {
            SutraBookmark::firstOrCreate([
                'user_id' => $user->id,
                'type' => 'sutra',
                'sutra_id' => $target->id,
            ]);
        }

        return response()->json([
            'data' => [
                'is_favorited' => true,
                'type' => $type,
                'content_public_id' => $publicId,
                'message' => 'Đã lưu vào danh sách yêu thích.',
            ],
        ]);
    }

    /**
     * Remove favorite (idempotent DELETE).
     */
    public function destroy(Request $request, string $type, string $publicId): JsonResponse
    {
        $user = $request->user();

        if (! array_key_exists($type, UserFavorite::ALLOWED_TYPES)) {
            return response()->json([
                'message' => 'Loại nội dung yêu thích không hợp lệ.',
            ], 422);
        }

        $target = $this->resolveTarget($type, $publicId);

        if ($target) {
            UserFavorite::query()
                ->forUser($user->id)
                ->ofType($type)
                ->where('favoritable_id', $target->id)
                ->delete();

            if ($type === 'sutra') {
                SutraBookmark::query()
                    ->forUser($user->id)
                    ->sutras()
                    ->where('sutra_id', $target->id)
                    ->delete();
            }
        } elseif (is_numeric($publicId)) {
            UserFavorite::query()
                ->forUser($user->id)
                ->ofType($type)
                ->where('favoritable_id', (int) $publicId)
                ->delete();
        }

        return response()->json([
            'data' => [
                'is_favorited' => false,
                'type' => $type,
                'content_public_id' => $publicId,
                'message' => 'Đã bỏ khỏi danh sách yêu thích.',
            ],
        ]);
    }

    /**
     * Check favorite status for a single item or batch of items.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'type' => 'required|string|in:sutra,article,video',
            'id' => 'nullable|string',
            'ids' => 'nullable|array|max:50',
            'ids.*' => 'string',
        ]);

        $type = (string) $request->input('type');
        $singleId = $request->input('id');
        $ids = $request->input('ids', []);

        // Single ID check
        if ($singleId !== null && empty($ids)) {
            $target = $this->resolveTarget($type, (string) $singleId);
            $isFavorited = false;

            if ($target) {
                $isFavorited = UserFavorite::query()
                    ->forUser($user->id)
                    ->ofType($type)
                    ->where('favoritable_id', $target->id)
                    ->exists();

                // Fallback check with SutraBookmark if sutra
                if (! $isFavorited && $type === 'sutra') {
                    $isFavorited = SutraBookmark::query()
                        ->forUser($user->id)
                        ->sutras()
                        ->where('sutra_id', $target->id)
                        ->exists();
                }
            }

            return response()->json([
                'data' => [
                    'is_favorited' => $isFavorited,
                    'type' => $type,
                    'content_public_id' => $singleId,
                ],
            ]);
        }

        // Batch IDs check
        $statuses = [];
        foreach ($ids as $idStr) {
            $target = $this->resolveTarget($type, (string) $idStr);
            $fav = false;

            if ($target) {
                $fav = UserFavorite::query()
                    ->forUser($user->id)
                    ->ofType($type)
                    ->where('favoritable_id', $target->id)
                    ->exists();

                if (! $fav && $type === 'sutra') {
                    $fav = SutraBookmark::query()
                        ->forUser($user->id)
                        ->sutras()
                        ->where('sutra_id', $target->id)
                        ->exists();
                }
            }

            $statuses[(string) $idStr] = $fav;
        }

        return response()->json([
            'data' => [
                'type' => $type,
                'statuses' => $statuses,
            ],
        ]);
    }

    /**
     * Resolve model instance by public identifier or ID.
     */
    protected function resolveTarget(string $type, string $publicId): ?Model
    {
        return match ($type) {
            'sutra' => Sutra::query()->where(function ($q) use ($publicId): void {
                $q->where('slug', $publicId);
                if (is_numeric($publicId)) {
                    $q->orWhere('id', (int) $publicId);
                }
            })->first(),

            'article' => Article::query()->where(function ($q) use ($publicId): void {
                $q->where('slug', $publicId);
                if (is_numeric($publicId)) {
                    $q->orWhere('id', (int) $publicId);
                }
            })->first(),

            'video' => BuddhistVideo::query()->where(function ($q) use ($publicId): void {
                $q->where('slug', $publicId)
                    ->orWhere('public_id', $publicId);
                if (is_numeric($publicId)) {
                    $q->orWhere('id', (int) $publicId);
                }
            })->first(),

            default => null,
        };
    }

    /**
     * Determine if target model is published.
     */
    protected function isTargetPublished(Model $target, string $type): bool
    {
        return match ($type) {
            'sutra' => $target->status === 'published'
                && ($target->published_at === null || $target->published_at->isPast()),

            'article' => method_exists($target, 'isPublished')
                ? $target->isPublished()
                : ($target->status === 'published'),

            'video' => ($target->editorial_status === VideoEditorialStatus::Published
                || $target->editorial_status?->value === VideoEditorialStatus::Published->value
                || $target->editorial_status === 'published')
                && ($target->published_at === null || $target->published_at->isPast()),

            default => false,
        };
    }
}
