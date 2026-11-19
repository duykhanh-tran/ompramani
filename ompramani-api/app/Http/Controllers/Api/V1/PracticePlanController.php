<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PracticePlanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Practice\StorePracticePlanRequest;
use App\Http\Requests\Api\V1\Practice\UpdatePracticePlanRequest;
use App\Http\Resources\Api\V1\PracticePlanResource;
use App\Models\PracticePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PracticePlanController extends Controller
{
    /**
     * Display a listing of user's practice plans.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $plans = PracticePlan::query()
            ->forUser($request->user())
            ->orderBy('status')
            ->orderByDesc('updated_at')
            ->get();

        return PracticePlanResource::collection($plans);
    }

    /**
     * Store a newly created practice plan.
     */
    public function store(StorePracticePlanRequest $request): JsonResponse
    {
        Gate::authorize('create', PracticePlan::class);

        $user = $request->user();
        $validated = $request->validated();

        $plan = PracticePlan::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'timezone' => $validated['timezone'] ?? $user->profile?->timezone ?? 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
            'starts_on' => $validated['starts_on'] ?? null,
            'ends_on' => $validated['ends_on'] ?? null,
        ]);

        return (new PracticePlanResource($plan))
            ->additional(['message' => 'Tạo kế hoạch tu tập thành công.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified practice plan with items.
     */
    public function show(Request $request, PracticePlan $practicePlan): PracticePlanResource
    {
        Gate::authorize('view', $practicePlan);

        $practicePlan->load(['items' => function ($query): void {
            $query->orderBy('sort_order')->orderBy('id');
        }]);

        return new PracticePlanResource($practicePlan);
    }

    /**
     * Update the specified practice plan.
     */
    public function update(UpdatePracticePlanRequest $request, PracticePlan $practicePlan): JsonResponse
    {
        Gate::authorize('update', $practicePlan);

        $practicePlan->update($request->validated());

        return (new PracticePlanResource($practicePlan))
            ->additional(['message' => 'Cập nhật kế hoạch tu tập thành công.'])
            ->response();
    }

    /**
     * Remove the specified practice plan.
     */
    public function destroy(Request $request, PracticePlan $practicePlan): JsonResponse
    {
        Gate::authorize('delete', $practicePlan);

        $practicePlan->delete();

        return response()->json([
            'message' => 'Đã xóa kế hoạch tu tập. Các buổi thực hành đã ghi nhận vẫn được bảo toàn nguyên vẹn.',
        ]);
    }

    /**
     * Pause the practice plan.
     */
    public function pause(Request $request, PracticePlan $practicePlan): JsonResponse
    {
        Gate::authorize('pause', $practicePlan);

        $practicePlan->update(['status' => PracticePlanStatus::Paused]);

        return (new PracticePlanResource($practicePlan))
            ->additional(['message' => 'Đã tạm dừng kế hoạch tu tập.'])
            ->response();
    }

    /**
     * Resume the practice plan.
     */
    public function resume(Request $request, PracticePlan $practicePlan): JsonResponse
    {
        Gate::authorize('resume', $practicePlan);

        $practicePlan->update(['status' => PracticePlanStatus::Active]);

        return (new PracticePlanResource($practicePlan))
            ->additional(['message' => 'Đã kích hoạt lại kế hoạch tu tập.'])
            ->response();
    }

    /**
     * Archive the practice plan.
     */
    public function archive(Request $request, PracticePlan $practicePlan): JsonResponse
    {
        Gate::authorize('archive', $practicePlan);

        $practicePlan->update(['status' => PracticePlanStatus::Archived]);

        return (new PracticePlanResource($practicePlan))
            ->additional(['message' => 'Đã lưu trữ kế hoạch tu tập.'])
            ->response();
    }
}
