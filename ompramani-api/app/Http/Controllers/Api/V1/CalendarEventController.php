<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Calendar\CalendarDayEventsRequest;
use App\Http\Requests\Api\V1\Calendar\CalendarEventsQueryRequest;
use App\Http\Requests\Api\V1\Calendar\CalendarMonthEventsRequest;
use App\Services\Calendar\CalendarEventOccurrenceService;
use App\Services\Calendar\CalendarEventService;
use App\Services\Calendar\CalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class CalendarEventController extends Controller
{
    public function __construct(
        protected CalendarEventOccurrenceService $occurrenceService,
        protected CalendarEventService $eventService,
        protected CalendarService $calendarService
    ) {}

    /**
     * Query calendar events within a date range [from, to].
     */
    public function index(CalendarEventsQueryRequest $request): JsonResponse
    {
        $from = (string) $request->input('from');
        $to = (string) $request->input('to');
        $tradition = $request->input('tradition') ? (string) $request->input('tradition') : null;
        $eventType = $request->input('event_type') ? (string) $request->input('event_type') : null;

        $occurrences = $this->occurrenceService->getOccurrencesBetween(
            $from,
            $to,
            $tradition,
            $eventType,
            true
        );

        return response()->json([
            'data' => $occurrences->values()->all(),
            'meta' => [
                'from' => $from,
                'to' => $to,
                'count' => $occurrences->count(),
                'tradition' => $tradition,
                'event_type' => $eventType,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get events and lunar info for a specific solar day (default: today).
     */
    public function day(CalendarDayEventsRequest $request): JsonResponse
    {
        $date = $request->input('date')
            ? (string) $request->input('date')
            : CarbonImmutable::now('Asia/Ho_Chi_Minh')->format('Y-m-d');

        $tradition = $request->input('tradition') ? (string) $request->input('tradition') : null;
        $eventType = $request->input('event_type') ? (string) $request->input('event_type') : null;

        $occurrences = $this->occurrenceService->getOccurrencesBetween(
            $date,
            $date,
            $tradition,
            $eventType,
            true
        );

        $dayInfo = $this->calendarService->getCalendarDay($date);

        return response()->json([
            'data' => [
                'date' => $date,
                'solar' => $dayInfo['solar'] ?? null,
                'lunar' => $dayInfo['lunar'] ?? null,
                'can_chi' => $dayInfo['can_chi'] ?? null,
                'tiet_khi' => $dayInfo['tiet_khi'] ?? null,
                'events' => $occurrences->values()->all(),
            ],
            'meta' => [
                'date' => $date,
                'count' => $occurrences->count(),
                'tradition' => $tradition,
                'event_type' => $eventType,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get events for an entire solar month.
     */
    public function month(CalendarMonthEventsRequest $request): JsonResponse
    {
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $tradition = $request->input('tradition') ? (string) $request->input('tradition') : null;
        $eventType = $request->input('event_type') ? (string) $request->input('event_type') : null;

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = CarbonImmutable::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

        $occurrences = $this->occurrenceService->getOccurrencesBetween(
            $startDate,
            $endDate,
            $tradition,
            $eventType,
            true
        );

        return response()->json([
            'data' => [
                'year' => $year,
                'month' => $month,
                'occurrences' => $occurrences->values()->all(),
                'grouped_by_date' => $occurrences->groupBy('solar_date')->all(),
            ],
            'meta' => [
                'year' => $year,
                'month' => $month,
                'count' => $occurrences->count(),
                'tradition' => $tradition,
                'event_type' => $eventType,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get details of a published calendar event by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $event = $this->eventService->getPublishedEventBySlug($slug);

        if ($event === null) {
            return response()->json([
                'message' => 'Sự kiện Phật giáo không tồn tại hoặc chưa được xuất bản.',
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return response()->json([
            'data' => $event,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get list of all calendar event types metadata.
     */
    public function types(): JsonResponse
    {
        $types = $this->eventService->getEventTypes();

        return response()->json([
            'data' => $types,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
