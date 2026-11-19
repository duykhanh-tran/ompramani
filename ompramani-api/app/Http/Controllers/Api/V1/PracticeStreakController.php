<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Practice\PracticeStreakQueryRequest;
use App\Models\User;
use App\Services\Practice\PracticeStreakService;
use Illuminate\Http\JsonResponse;

class PracticeStreakController extends Controller
{
    public function __construct(
        protected PracticeStreakService $streakService
    ) {}

    /**
     * Get practice streak metrics and activity calendar for the authenticated user.
     */
    public function show(PracticeStreakQueryRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $days = $request->has('days') ? (int) $request->input('days') : 30;
        $timezone = $request->input('timezone');

        $data = $this->streakService->calculateStreak($user, $days, $timezone);

        return response()->json([
            'data' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
