<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PracticeReminderScheduleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Practice\StorePracticePlanItemRequest;
use App\Http\Requests\Api\V1\Practice\UpdatePracticePlanItemRequest;
use App\Http\Resources\Api\V1\PracticePlanItemResource;
use App\Models\PracticePlan;
use App\Models\PracticePlanItem;
use App\Models\PracticeReminder;
use App\Services\Practice\PracticeReminderScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PracticePlanItemController extends Controller
{
    public function __construct(
        protected PracticeReminderScheduleService $reminderScheduleService
    ) {}

    /**
     * Display a listing of items for a practice plan.
     */
    public function index(Request $request, PracticePlan $practicePlan): AnonymousResourceCollection
    {
        Gate::authorize('view', $practicePlan);

        return PracticePlanItemResource::collection($practicePlan->items);
    }

    /**
     * Store a newly created item in the practice plan.
     */
    public function store(StorePracticePlanItemRequest $request, PracticePlan $practicePlan): JsonResponse
    {
        Gate::authorize('create', [PracticePlanItem::class, $practicePlan]);

        $user = $request->user();
        $validated = $request->validated();

        $reminderId = null;

        // Optional Opt-in Reminder Integration (Task 36)
        if ($request->boolean('create_reminder') && ! empty($validated['local_time'])) {
            $remSchedType = $validated['schedule_type'] === 'once'
                ? PracticeReminderScheduleType::Once
                : PracticeReminderScheduleType::Weekly;

            $reminder = PracticeReminder::create([
                'user_id' => $user->id,
                'practice_type' => $validated['practice_type'],
                'title' => $validated['title'] ?? null,
                'schedule_type' => $remSchedType,
                'local_time' => $validated['local_time'],
                'timezone' => $practicePlan->timezone ?: 'Asia/Ho_Chi_Minh',
                'local_date' => $validated['local_date'] ?? null,
                'weekdays' => $validated['weekdays'] ?? [],
                'is_enabled' => true,
                'schedule_revision' => 1,
            ]);

            // Calculate initial next_due_at_utc
            $nextDue = $this->reminderScheduleService->calculateNextDue($reminder);
            $reminder->update(['next_due_at_utc' => $nextDue]);

            $reminderId = $reminder->id;
        }

        $item = PracticePlanItem::create([
            'practice_plan_id' => $practicePlan->id,
            'user_id' => $user->id,
            'practice_type' => $validated['practice_type'],
            'title' => $validated['title'] ?? null,
            'schedule_type' => $validated['schedule_type'],
            'local_date' => $validated['local_date'] ?? null,
            'local_time' => $validated['local_time'] ?? null,
            'weekdays' => $validated['weekdays'] ?? [],
            'target_duration_seconds' => $validated['target_duration_seconds'] ?? null,
            'target_repetitions' => $validated['target_repetitions'] ?? null,
            'starts_on' => $validated['starts_on'] ?? null,
            'ends_on' => $validated['ends_on'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_enabled' => true,
            'schedule_revision' => 1,
            'practice_reminder_id' => $reminderId,
        ]);

        return (new PracticePlanItemResource($item))
            ->additional(['message' => 'Thêm hoạt động tu tập vào kế hoạch thành công.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified item.
     */
    public function show(Request $request, PracticePlanItem $practicePlanItem): PracticePlanItemResource
    {
        Gate::authorize('view', $practicePlanItem);

        return new PracticePlanItemResource($practicePlanItem);
    }

    /**
     * Update the specified item.
     */
    public function update(UpdatePracticePlanItemRequest $request, PracticePlanItem $practicePlanItem): JsonResponse
    {
        Gate::authorize('update', $practicePlanItem);

        $validated = $request->validated();

        // Detect schedule modification to bump schedule_revision
        $scheduleFields = ['schedule_type', 'local_date', 'local_time', 'weekdays'];
        $scheduleChanged = false;
        foreach ($scheduleFields as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] != $practicePlanItem->{$field}) {
                $scheduleChanged = true;
                break;
            }
        }

        if ($scheduleChanged) {
            $validated['schedule_revision'] = $practicePlanItem->schedule_revision + 1;
        }

        $practicePlanItem->update($validated);

        // Optional Sync with linked PracticeReminder if requested
        if ($request->boolean('sync_reminder') && $practicePlanItem->practice_reminder_id) {
            $reminder = PracticeReminder::find($practicePlanItem->practice_reminder_id);
            if ($reminder && $reminder->user_id === $request->user()->id) {
                $remSchedType = $practicePlanItem->schedule_type->value === 'once'
                    ? PracticeReminderScheduleType::Once
                    : PracticeReminderScheduleType::Weekly;

                $reminder->update([
                    'practice_type' => $practicePlanItem->practice_type,
                    'title' => $practicePlanItem->title,
                    'schedule_type' => $remSchedType,
                    'local_time' => $practicePlanItem->local_time ?: '06:00',
                    'local_date' => $practicePlanItem->local_date,
                    'weekdays' => $practicePlanItem->weekdays,
                    'schedule_revision' => $reminder->schedule_revision + 1,
                ]);

                $nextDue = $this->reminderScheduleService->calculateNextDue($reminder);
                $reminder->update(['next_due_at_utc' => $nextDue]);
            }
        }

        return (new PracticePlanItemResource($practicePlanItem))
            ->additional(['message' => 'Cập nhật hoạt động tu tập thành công.'])
            ->response();
    }

    /**
     * Remove the specified item.
     */
    public function destroy(Request $request, PracticePlanItem $practicePlanItem): JsonResponse
    {
        Gate::authorize('delete', $practicePlanItem);

        $practicePlanItem->delete();

        return response()->json([
            'message' => 'Đã xóa hoạt động khỏi kế hoạch tu tập.',
        ]);
    }
}
