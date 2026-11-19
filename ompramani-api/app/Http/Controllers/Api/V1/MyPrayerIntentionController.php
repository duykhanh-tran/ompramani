<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Prayer\StorePrayerIntentionRequest;
use App\Http\Requests\Api\V1\Prayer\SubmitPrayerIntentionRequest;
use App\Http\Requests\Api\V1\Prayer\UpdatePrayerIntentionRequest;
use App\Http\Resources\Api\V1\MyPrayerIntentionResource;
use App\Models\PrayerIntention;
use App\Services\Prayer\PrayerIntentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyPrayerIntentionController extends Controller
{
    public function __construct(
        protected PrayerIntentionService $prayerService
    ) {}

    /**
     * List authenticated user's prayer intentions.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $query = PrayerIntention::forUser($user->id);

        // Optional filtering by tab / status
        $tab = $request->input('tab', 'all');
        match ($tab) {
            'private' => $query->where('visibility', PrayerVisibility::Private->value),
            'pending' => $query->where('visibility', PrayerVisibility::Public->value)
                ->where('moderation_status', PrayerModerationStatus::Pending->value)
                ->whereNull('withdrawn_at'),
            'shared' => $query->where('visibility', PrayerVisibility::Public->value)
                ->where('moderation_status', PrayerModerationStatus::Approved->value)
                ->whereNull('withdrawn_at'),
            'rejected' => $query->where('moderation_status', PrayerModerationStatus::Rejected->value),
            default => null,
        };

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $intentions = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return MyPrayerIntentionResource::collection($intentions);
    }

    /**
     * Store a new prayer intention.
     */
    public function store(StorePrayerIntentionRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $isPublic = ($validated['visibility'] ?? null) === PrayerVisibility::Public->value;
        $consentGiven = (bool) ($validated['consent_public_sharing'] ?? false);

        if ($isPublic) {
            $intention = $this->prayerService->createPublic($user, $validated, $consentGiven);
        } else {
            $intention = $this->prayerService->createPrivate($user, $validated);
        }

        return (new MyPrayerIntentionResource($intention))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a personal prayer intention by ID.
     */
    public function show(Request $request, int $id): MyPrayerIntentionResource
    {
        $user = $request->user();

        $intention = PrayerIntention::forUser($user->id)
            ->where('id', $id)
            ->firstOrFail();

        return new MyPrayerIntentionResource($intention);
    }

    /**
     * Update a personal prayer intention.
     */
    public function update(UpdatePrayerIntentionRequest $request, int $id): MyPrayerIntentionResource
    {
        $user = $request->user();

        $intention = PrayerIntention::forUser($user->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validated();
        $requestPublic = isset($validated['visibility'])
            ? ($validated['visibility'] === PrayerVisibility::Public->value)
            : null;
        $consentGiven = (bool) ($validated['consent_public_sharing'] ?? false);

        $updated = $this->prayerService->update(
            $user,
            $intention,
            $validated,
            $requestPublic,
            $consentGiven
        );

        return new MyPrayerIntentionResource($updated);
    }

    /**
     * Soft delete a personal prayer intention.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $intention = PrayerIntention::forUser($user->id)
            ->where('id', $id)
            ->firstOrFail();

        $this->prayerService->delete($user, $intention);

        return response()->json([
            'message' => 'Đã xóa lời nguyện thành công.',
        ]);
    }

    /**
     * Submit an existing intention for public moderation review.
     */
    public function submit(SubmitPrayerIntentionRequest $request, int $id): MyPrayerIntentionResource
    {
        $user = $request->user();

        $intention = PrayerIntention::forUser($user->id)
            ->where('id', $id)
            ->firstOrFail();

        $submitted = $this->prayerService->submitForReview(
            $user,
            $intention,
            (bool) $request->validated('consent_public_sharing')
        );

        return new MyPrayerIntentionResource($submitted);
    }

    /**
     * Stop sharing / withdraw an intention immediately from community exposure.
     */
    public function withdraw(Request $request, int $id): MyPrayerIntentionResource
    {
        $user = $request->user();

        $intention = PrayerIntention::forUser($user->id)
            ->where('id', $id)
            ->firstOrFail();

        $withdrawn = $this->prayerService->withdraw($user, $intention);

        return new MyPrayerIntentionResource($withdrawn);
    }
}
