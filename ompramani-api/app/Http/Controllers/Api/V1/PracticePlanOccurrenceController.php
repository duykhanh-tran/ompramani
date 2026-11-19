<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Practice\LinkSessionRequest;
use App\Http\Requests\Api\V1\Practice\OccurrenceActionRequest;
use App\Services\Practice\PracticePlanOccurrenceService;
use App\Services\Practice\PracticePlanSessionLinkService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PracticePlanOccurrenceController extends Controller
{
    public function __construct(
        protected PracticePlanOccurrenceService $occurrenceService,
        protected PracticePlanSessionLinkService $linkService
    ) {}

    /**
     * Get planned occurrences for a date range.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
            'plan_id' => ['nullable', 'integer', 'exists:practice_plans,id'],
        ], [
            'from.required' => 'Vui lòng cung cấp ngày bắt đầu (from).',
            'to.required' => 'Vui lòng cung cấp ngày kết thúc (to).',
        ]);

        $user = $request->user();
        $from = $request->input('from');
        $to = $request->input('to');
        $planId = $request->input('plan_id') ? (int) $request->input('plan_id') : null;

        $occurrences = $this->occurrenceService->generateOccurrences($user, $from, $to, $planId);

        return response()->json([
            'data' => $occurrences,
            'meta' => [
                'from' => $from,
                'to' => $to,
                'total' => count($occurrences),
            ],
        ]);
    }

    /**
     * Convenience endpoint: Get today's planned occurrences.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $timezone = $user->profile?->timezone ?? 'Asia/Ho_Chi_Minh';
        $todayStr = Carbon::now($timezone)->format('Y-m-d');

        $occurrences = $this->occurrenceService->generateOccurrences(
            $user,
            $todayStr,
            $todayStr,
            $request->input('plan_id') ? (int) $request->input('plan_id') : null
        );

        return response()->json([
            'data' => $occurrences,
            'today' => $todayStr,
            'timezone' => $timezone,
        ]);
    }

    /**
     * Convenience endpoint: Get current week's planned occurrences (Monday to Sunday).
     */
    public function week(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'plan_id' => ['nullable', 'integer', 'exists:practice_plans,id'],
        ]);

        $user = $request->user();
        $timezone = $user->profile?->timezone ?? 'Asia/Ho_Chi_Minh';
        $weekRange = $this->occurrenceService->getWeekRange($request->input('date'), $timezone);

        $occurrences = $this->occurrenceService->generateOccurrences(
            $user,
            $weekRange['from'],
            $weekRange['to'],
            $request->input('plan_id') ? (int) $request->input('plan_id') : null
        );

        return response()->json([
            'data' => $occurrences,
            'week' => $weekRange,
            'timezone' => $timezone,
        ]);
    }

    /**
     * Link an authoritative completed PracticeSession to an occurrence.
     */
    public function linkSession(LinkSessionRequest $request): JsonResponse
    {
        $link = $this->linkService->linkSession(
            $request->user(),
            (int) $request->input('plan_item_id'),
            $request->input('local_date'),
            (int) $request->input('practice_session_id')
        );

        return response()->json([
            'message' => 'Đã liên kết buổi thực hành với hoạt động trong kế hoạch thành công.',
            'data' => [
                'id' => $link->id,
                'plan_item_id' => $link->practice_plan_item_id,
                'local_date' => $link->occurrence_local_date->format('Y-m-d'),
                'practice_session_id' => $link->practice_session_id,
                'linked_at' => $link->linked_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Unlink a PracticeSession from an occurrence.
     */
    public function unlinkSession(OccurrenceActionRequest $request): JsonResponse
    {
        $this->linkService->unlinkSession(
            $request->user(),
            (int) $request->input('plan_item_id'),
            $request->input('local_date')
        );

        return response()->json([
            'message' => 'Đã gỡ liên kết buổi thực hành khỏi kế hoạch. Dữ liệu thực hành gốc vẫn được lưu trữ nguyên vẹn.',
        ]);
    }

    /**
     * Get candidate completed PracticeSessions for linking.
     */
    public function candidateSessions(Request $request): JsonResponse
    {
        $request->validate([
            'plan_item_id' => ['required', 'integer', 'exists:practice_plan_items,id'],
            'local_date' => ['required', 'date_format:Y-m-d'],
        ]);

        $candidates = $this->linkService->getCandidateSessions(
            $request->user(),
            (int) $request->input('plan_item_id'),
            $request->input('local_date')
        );

        $data = $candidates->map(function ($s): array {
            $reps = null;
            if (isset($s->progress_data['repetition_count'])) {
                $reps = (int) $s->progress_data['repetition_count'];
            } elseif (isset($s->progress_data['bead_count'])) {
                $reps = (int) $s->progress_data['bead_count'];
            }

            return [
                'id' => $s->id,
                'practice_type' => $s->practice_type->value,
                'practice_label' => $s->practice_type->label(),
                'active_duration_seconds' => (int) $s->active_duration_seconds,
                'repetition_count' => $reps,
                'started_at' => $s->started_at?->toIso8601String(),
                'ended_at' => $s->ended_at?->toIso8601String(),
                'status' => $s->status->value,
                'status_label' => $s->status->label(),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Skip a planned occurrence.
     */
    public function skip(OccurrenceActionRequest $request): JsonResponse
    {
        $override = $this->linkService->skipOccurrence(
            $request->user(),
            (int) $request->input('plan_item_id'),
            $request->input('local_date'),
            $request->input('reason')
        );

        return response()->json([
            'message' => 'Đã ghi nhận bỏ qua hoạt động cho lần này trong an tĩnh.',
            'data' => [
                'plan_item_id' => $override->practice_plan_item_id,
                'local_date' => $override->local_date->format('Y-m-d'),
                'action' => $override->action,
            ],
        ]);
    }

    /**
     * Restore a skipped occurrence.
     */
    public function restore(OccurrenceActionRequest $request): JsonResponse
    {
        $this->linkService->restoreOccurrence(
            $request->user(),
            (int) $request->input('plan_item_id'),
            $request->input('local_date')
        );

        return response()->json([
            'message' => 'Đã khôi phục hoạt động dự kiến trong kế hoạch.',
        ]);
    }
}
