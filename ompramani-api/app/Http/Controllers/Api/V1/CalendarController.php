<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Calendar\CalendarDayRequest;
use App\Http\Requests\Api\V1\Calendar\CalendarMonthRequest;
use App\Http\Requests\Api\V1\Calendar\LunarToSolarRequest;
use App\Http\Requests\Api\V1\Calendar\SolarToLunarRequest;
use App\Services\Calendar\CalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    /**
     * Convert Gregorian solar date to Vietnamese lunisolar date.
     */
    public function solarToLunar(SolarToLunarRequest $request): JsonResponse
    {
        $date = $request->resolvedDate();

        if ($date === '') {
            throw ValidationException::withMessages([
                'date' => ['Vui lòng cung cấp tham số date (YYYY-MM-DD) hoặc bộ tham số year, month, day.'],
            ]);
        }

        $result = $this->calendarService->solarToLunar($date);

        return response()->json([
            'data' => $result,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Convert Vietnamese lunisolar date to Gregorian solar date.
     */
    public function lunarToSolar(LunarToSolarRequest $request): JsonResponse
    {
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $day = (int) $request->input('day');
        $isLeapMonth = $request->boolean('is_leap_month');

        $result = $this->calendarService->lunarToSolar($year, $month, $day, $isLeapMonth);

        return response()->json([
            'data' => $result,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get day calendar data (default: today in Asia/Ho_Chi_Minh).
     */
    public function day(CalendarDayRequest $request): JsonResponse
    {
        $date = $request->input('date');
        $result = $this->calendarService->getCalendarDay($date ? (string) $date : null);

        return response()->json([
            'data' => $result,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get whole-month calendar grid data for a solar month.
     */
    public function month(CalendarMonthRequest $request): JsonResponse
    {
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        $result = $this->calendarService->getCalendarMonth($year, $month);

        return response()->json([
            'data' => $result,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get 7-day weekly calendar strip data around a reference date.
     */
    public function week(CalendarDayRequest $request): JsonResponse
    {
        $date = $request->input('date');
        $result = $this->calendarService->getCalendarWeek($date ? (string) $date : null);

        return response()->json([
            'data' => $result,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get calendar configuration and supported capabilities.
     */
    public function config(): JsonResponse
    {
        $result = $this->calendarService->getConfig();

        return response()->json([
            'data' => $result,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
