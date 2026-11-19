<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PracticeReminderScheduleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Practice\StorePracticeReminderRequest;
use App\Http\Requests\Api\V1\Practice\UpdatePracticeReminderRequest;
use App\Http\Resources\Api\V1\PracticeReminderResource;
use App\Models\PracticeReminder;
use App\Services\Practice\PracticeReminderScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PracticeReminderController extends Controller
{
    public function __construct(
        protected PracticeReminderScheduleService $scheduleService
    ) {}

    /**
     * List all practice reminders for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reminders = $request->user()
            ->practiceReminders()
            ->latest()
            ->get();

        return PracticeReminderResource::collection($reminders);
    }

    /**
     * Create a new practice reminder.
     */
    public function store(StorePracticeReminderRequest $request): JsonResponse
    {
        $user = $request->user();

        // Enforce user limit (max 20 active reminders)
        $activeCount = $user->practiceReminders()->where('is_enabled', true)->count();
        if ($activeCount >= 20) {
            throw new HttpException(422, 'Bạn đã đạt giới hạn tối đa 20 lời nhắc tu tập đang kích hoạt.');
        }

        $validated = $request->validated();
        $isEnabled = $validated['is_enabled'] ?? true;

        $reminder = new PracticeReminder;
        $reminder->user_id = $user->id;
        $reminder->practice_type = $validated['practice_type'];
        $reminder->title = ! empty($validated['title']) ? trim($validated['title']) : null;
        $reminder->schedule_type = $validated['schedule_type'];
        $reminder->local_time = $validated['local_time'];
        $reminder->timezone = $validated['timezone'];
        $reminder->local_date = $validated['local_date'] ?? null;
        $reminder->weekdays = $validated['weekdays'] ?? null;
        $reminder->is_enabled = (bool) $isEnabled;
        $reminder->schedule_revision = 1;

        if ($reminder->is_enabled) {
            $reminder->next_due_at_utc = $this->scheduleService->calculateNextDue($reminder);
        } else {
            $reminder->next_due_at_utc = null;
        }

        $reminder->save();

        return (new PracticeReminderResource($reminder))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * View an individual practice reminder.
     */
    public function show(Request $request, PracticeReminder $practiceReminder): PracticeReminderResource
    {
        $this->authorizeReminder($request, $practiceReminder);

        return new PracticeReminderResource($practiceReminder);
    }

    /**
     * Update an existing practice reminder.
     */
    public function update(UpdatePracticeReminderRequest $request, PracticeReminder $practiceReminder): PracticeReminderResource
    {
        $this->authorizeReminder($request, $practiceReminder);

        $validated = $request->validated();

        if (array_key_exists('practice_type', $validated)) {
            $practiceReminder->practice_type = $validated['practice_type'];
        }
        if (array_key_exists('title', $validated)) {
            $practiceReminder->title = ! empty($validated['title']) ? trim($validated['title']) : null;
        }
        if (array_key_exists('schedule_type', $validated)) {
            $practiceReminder->schedule_type = $validated['schedule_type'];
        }
        if (array_key_exists('local_time', $validated)) {
            $practiceReminder->local_time = $validated['local_time'];
        }
        if (array_key_exists('timezone', $validated)) {
            $practiceReminder->timezone = $validated['timezone'];
        }
        if (array_key_exists('local_date', $validated)) {
            $practiceReminder->local_date = $validated['local_date'];
        }
        if (array_key_exists('weekdays', $validated)) {
            $practiceReminder->weekdays = $validated['weekdays'];
        }
        if (array_key_exists('is_enabled', $validated)) {
            $practiceReminder->is_enabled = (bool) $validated['is_enabled'];
        }

        // Invalidate previous schedule occurrence by advancing revision
        $practiceReminder->schedule_revision++;

        if ($practiceReminder->is_enabled) {
            $practiceReminder->next_due_at_utc = $this->scheduleService->calculateNextDue($practiceReminder);
        } else {
            $practiceReminder->next_due_at_utc = null;
        }

        $practiceReminder->save();

        return new PracticeReminderResource($practiceReminder);
    }

    /**
     * Delete / remove a practice reminder.
     */
    public function destroy(Request $request, PracticeReminder $practiceReminder): JsonResponse
    {
        $this->authorizeReminder($request, $practiceReminder);

        $practiceReminder->delete();

        return response()->json([
            'message' => 'Đã xóa lời nhắc tu tập thành công.',
        ]);
    }

    /**
     * Pause an active practice reminder.
     */
    public function pause(Request $request, PracticeReminder $practiceReminder): PracticeReminderResource
    {
        $this->authorizeReminder($request, $practiceReminder);

        $practiceReminder->is_enabled = false;
        $practiceReminder->schedule_revision++;
        $practiceReminder->next_due_at_utc = null;
        $practiceReminder->save();

        return new PracticeReminderResource($practiceReminder);
    }

    /**
     * Resume a paused practice reminder.
     */
    public function resume(Request $request, PracticeReminder $practiceReminder): PracticeReminderResource
    {
        $this->authorizeReminder($request, $practiceReminder);

        // If it's a one-time reminder, verify that the date has not passed
        $nextDue = $this->scheduleService->calculateNextDue($practiceReminder);

        if ($practiceReminder->schedule_type === PracticeReminderScheduleType::Once && ! $nextDue) {
            throw new UnprocessableEntityHttpException('Lịch nhắc một lần này đã qua thời gian dự kiến. Vui lòng chỉnh sửa thời gian mới để tiếp tục.');
        }

        $practiceReminder->is_enabled = true;
        $practiceReminder->schedule_revision++;
        $practiceReminder->next_due_at_utc = $nextDue;
        $practiceReminder->save();

        return new PracticeReminderResource($practiceReminder);
    }

    /**
     * Authorize that the current user owns this reminder.
     */
    protected function authorizeReminder(Request $request, PracticeReminder $reminder): void
    {
        if ($reminder->user_id !== $request->user()->id) {
            abort(404, 'Lời nhắc không tồn tại hoặc bạn không có quyền truy cập.');
        }
    }
}
