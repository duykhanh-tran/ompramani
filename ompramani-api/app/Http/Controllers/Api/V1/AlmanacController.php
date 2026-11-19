<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Almanac\AlmanacDayRequest;
use App\Http\Requests\Api\V1\Almanac\AlmanacMonthRequest;
use App\Services\Almanac\AlmanacService;
use App\Services\Almanac\Constants\EarthlyBranch;
use App\Services\Almanac\Constants\HeavenlyStem;
use App\Services\Almanac\Constants\YellowBlackDeity;
use App\Services\Almanac\Providers\ThanhLongAlmanacProvider;
use Illuminate\Http\JsonResponse;

class AlmanacController extends Controller
{
    public function __construct(
        protected AlmanacService $almanacService
    ) {}

    /**
     * Get almanac day data (Can Chi, Day classification, 12 Earthly branch hours, Auspicious hours).
     */
    public function day(AlmanacDayRequest $request): JsonResponse
    {
        $data = $this->almanacService->getDay(
            $request->input('date'),
            $request->input('method')
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get almanac monthly summaries for all days in a month.
     */
    public function month(AlmanacMonthRequest $request): JsonResponse
    {
        $data = $this->almanacService->getMonth(
            (int) $request->input('year'),
            (int) $request->input('month'),
            $request->input('method')
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * List all available almanac methods and metadata.
     */
    public function methods(): JsonResponse
    {
        $methods = $this->almanacService->getAvailableMethods();

        return response()->json([
            'data' => $methods,
        ]);
    }

    /**
     * Get almanac system configuration and reference registries.
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'data' => [
                'system' => 'traditional_almanac',
                'calendar_timezone' => AlmanacService::TIMEZONE,
                'earthly_branches' => array_values(EarthlyBranch::ALL),
                'heavenly_stems' => array_values(HeavenlyStem::ALL),
                'yellow_black_deities' => array_values(YellowBlackDeity::ALL),
                'supported_range' => [
                    'start_date' => ThanhLongAlmanacProvider::MIN_SUPPORTED_DATE,
                    'end_date' => ThanhLongAlmanacProvider::MAX_SUPPORTED_DATE,
                ],
                'disclaimer' => 'Thông tin ngày giờ theo lịch truyền thống, mang tính tham khảo văn hóa dân gian. Không phải giáo lý Phật giáo. Không dùng để bói toán hay quyết định vận mệnh.',
            ],
        ]);
    }
}
