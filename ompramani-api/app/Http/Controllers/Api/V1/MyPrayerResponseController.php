<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Prayer\UpdatePrayerResponseRequest;
use App\Http\Resources\Api\V1\MyPrayerResponseResource;
use App\Models\PrayerResponse;
use App\Services\Prayer\PrayerResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyPrayerResponseController extends Controller
{
    public function __construct(
        protected PrayerResponseService $responseService
    ) {}

    /**
     * List current user's submitted responses.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $responses = PrayerResponse::where('user_id', $request->user()->id)
            ->with('prayerIntention')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return MyPrayerResponseResource::collection($responses);
    }

    /**
     * Show single personal response.
     */
    public function show(Request $request, int $id): MyPrayerResponseResource
    {
        $response = PrayerResponse::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('prayerIntention')
            ->firstOrFail();

        return new MyPrayerResponseResource($response);
    }

    /**
     * Update an existing personal response.
     */
    public function update(UpdatePrayerResponseRequest $request, int $id): JsonResponse
    {
        $response = $this->responseService->updateResponse(
            $request->user(),
            $id,
            (string) $request->validated('body')
        );

        return (new MyPrayerResponseResource($response))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Withdraw a personal response.
     */
    public function withdraw(Request $request, int $id): JsonResponse
    {
        $response = $this->responseService->withdrawResponse($request->user(), $id);

        return (new MyPrayerResponseResource($response))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Delete a personal response (Soft delete).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->responseService->deleteResponse($request->user(), $id);

        return response()->json([
            'message' => 'Đã xóa lời nhắn thành công.',
        ], 200);
    }

    /**
     * Resubmit a rejected response with updated body.
     */
    public function resubmit(UpdatePrayerResponseRequest $request, int $id): JsonResponse
    {
        $response = $this->responseService->resubmitResponse(
            $request->user(),
            $id,
            (string) $request->validated('body')
        );

        return (new MyPrayerResponseResource($response))
            ->response()
            ->setStatusCode(200);
    }
}
