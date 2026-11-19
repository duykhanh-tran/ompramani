<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Prayer\ReportPrayerResponseRequest;
use App\Services\Prayer\PrayerResponseService;
use Illuminate\Http\JsonResponse;

class PrayerResponseReportController extends Controller
{
    public function __construct(
        protected PrayerResponseService $responseService
    ) {}

    /**
     * Submit an abuse/safety report against an approved public response.
     */
    public function store(ReportPrayerResponseRequest $request, string $publicId): JsonResponse
    {
        $report = $this->responseService->reportResponse(
            $request->user(),
            $publicId,
            (string) $request->validated('reason'),
            $request->validated('details')
        );

        return response()->json([
            'message' => 'Đã ghi nhận báo cáo nội dung của bạn. Cảm ơn bạn đã chung tay bảo vệ sự thanh tịnh cho không gian cộng đồng.',
            'data' => [
                'id' => $report->id,
                'status' => $report->status->value,
            ],
        ], 201);
    }
}
