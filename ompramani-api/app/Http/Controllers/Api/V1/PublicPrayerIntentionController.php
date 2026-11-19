<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PrayerCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PublicPrayerIntentionResource;
use App\Models\PrayerIntention;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicPrayerIntentionController extends Controller
{
    /**
     * List public approved prayer intentions (Community Feed).
     * Strictly excludes private, pending, rejected, or withdrawn records.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->input('per_page', 12), 1), 50);

        $query = PrayerIntention::publicApproved();

        if ($request->filled('category')) {
            $category = $request->input('category');
            if (PrayerCategory::tryFrom((string) $category) !== null) {
                $query->where('category', $category);
            }
        }

        $intentions = $query->withCount(['activeParticipations', 'approvedResponses'])
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return PublicPrayerIntentionResource::collection($intentions);
    }

    /**
     * Show a single public approved prayer intention by its opaque public_id.
     */
    public function show(string $publicId): PublicPrayerIntentionResource
    {
        $intention = PrayerIntention::publicApproved()
            ->where('public_id', $publicId)
            ->withCount(['activeParticipations', 'approvedResponses'])
            ->firstOrFail();

        return new PublicPrayerIntentionResource($intention);
    }

    /**
     * Get list of supported prayer intention categories.
     */
    public function categories(): JsonResponse
    {
        $categories = collect(PrayerCategory::cases())->map(fn (PrayerCategory $c) => [
            'key' => $c->value,
            'label' => $c->label(),
        ])->values();

        return response()->json([
            'data' => $categories,
        ]);
    }
}
