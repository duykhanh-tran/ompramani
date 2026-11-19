<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DharmaJourney\DharmaJourneyQueryRequest;
use App\Services\DharmaJourney\DharmaJourneyService;
use Illuminate\Http\JsonResponse;

class DharmaJourneyController extends Controller
{
    public function __construct(
        public DharmaJourneyService $service
    ) {}

    /**
     * Get complete dharma journey dashboard payload.
     */
    public function index(DharmaJourneyQueryRequest $request): JsonResponse
    {
        $range = $request->validated('range', '30d');
        $timezone = $request->validated('timezone');

        $data = $this->service->getDashboard($request->user(), $range ?? '30d', $timezone);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get summary metrics for the user in the selected range.
     */
    public function summary(DharmaJourneyQueryRequest $request): JsonResponse
    {
        $range = $request->validated('range', '30d');
        $timezone = $request->validated('timezone');

        $data = $this->service->getSummary($request->user(), $range ?? '30d', $timezone);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get daily activity chart data for the user in the selected range.
     */
    public function activity(DharmaJourneyQueryRequest $request): JsonResponse
    {
        $range = $request->validated('range', '30d');
        $timezone = $request->validated('timezone');

        $data = $this->service->getActivityChart($request->user(), $range ?? '30d', $timezone);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get practice type breakdown for the user in the selected range.
     */
    public function breakdown(DharmaJourneyQueryRequest $request): JsonResponse
    {
        $range = $request->validated('range', '30d');
        $timezone = $request->validated('timezone');

        $data = $this->service->getBreakdown($request->user(), $range ?? '30d', $timezone);

        return response()->json([
            'data' => $data,
        ]);
    }
}
