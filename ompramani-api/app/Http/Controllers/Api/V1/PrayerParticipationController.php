<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MyPrayerParticipationResource;
use App\Services\Prayer\PrayerParticipationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PrayerParticipationController extends Controller
{
    public function __construct(
        protected PrayerParticipationService $participationService
    ) {}

    /**
     * Express companionship with a public prayer intention (Join).
     * Idempotent PUT method: returns current active status and fresh count.
     */
    public function participate(Request $request, string $publicId): JsonResponse
    {
        $result = $this->participationService->participate($request->user(), $publicId);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Withdraw companionship from a prayer intention (Withdraw).
     * Idempotent DELETE method: returns non-active status and fresh count.
     */
    public function withdraw(Request $request, string $publicId): JsonResponse
    {
        $result = $this->participationService->withdraw($request->user(), $publicId);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Batch check viewer participation statuses for up to 50 public prayer intentions.
     */
    public function status(Request $request): JsonResponse
    {
        $rawIds = $request->input('public_ids', []);

        if (is_string($rawIds)) {
            $ids = array_map('trim', explode(',', $rawIds));
        } elseif (is_array($rawIds)) {
            $ids = $rawIds;
        } else {
            $ids = [];
        }

        $statuses = $this->participationService->getBatchStatus($request->user(), $ids);

        return response()->json([
            'data' => [
                'statuses' => $statuses,
            ],
        ]);
    }

    /**
     * View personal active participation history.
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->input('per_page', 12), 1), 50);

        $participations = $this->participationService->getHistoryForUser($request->user(), $perPage);

        return MyPrayerParticipationResource::collection($participations);
    }
}
