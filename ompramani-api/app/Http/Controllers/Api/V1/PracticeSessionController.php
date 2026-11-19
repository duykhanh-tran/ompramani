<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Practice\CompletePracticeSessionRequest;
use App\Http\Requests\Api\V1\Practice\ListPracticeSessionsRequest;
use App\Http\Requests\Api\V1\Practice\StartPracticeSessionRequest;
use App\Http\Requests\Api\V1\Practice\UpdatePracticeProgressRequest;
use App\Http\Resources\Api\V1\PracticeSessionResource;
use App\Models\PracticeSession;
use App\Services\Practice\PracticeSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PracticeSessionController extends Controller
{
    public function __construct(
        protected PracticeSessionService $service
    ) {}

    /**
     * List paginated practice session history for the authenticated user.
     */
    public function index(ListPracticeSessionsRequest $request): AnonymousResourceCollection
    {
        $sessions = $this->service->getUserSessions($request->user(), $request->validated());

        return PracticeSessionResource::collection($sessions);
    }

    /**
     * Start a new practice session.
     */
    public function store(StartPracticeSessionRequest $request): JsonResponse
    {
        $session = $this->service->startSession($request->user(), $request->validated());
        $statusCode = $session->wasRecentlyCreated ? 201 : 200;

        return (new PracticeSessionResource($session))
            ->response()
            ->setStatusCode($statusCode);
    }

    /**
     * Get current active or paused session for the authenticated user.
     */
    public function active(Request $request): JsonResponse
    {
        $session = $this->service->getActiveSession($request->user());

        if (! $session) {
            return response()->json(['data' => null]);
        }

        return (new PracticeSessionResource($session))->response();
    }

    /**
     * View details of a specific practice session.
     */
    public function show(int $id, Request $request): PracticeSessionResource
    {
        $session = PracticeSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return new PracticeSessionResource($session);
    }

    /**
     * Pause an active practice session.
     */
    public function pause(int $id, Request $request): PracticeSessionResource
    {
        $session = PracticeSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $updated = $this->service->pauseSession($session, $request->user());

        return new PracticeSessionResource($updated);
    }

    /**
     * Resume a paused practice session.
     */
    public function resume(int $id, Request $request): PracticeSessionResource
    {
        $session = PracticeSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $updated = $this->service->resumeSession($session, $request->user());

        return new PracticeSessionResource($updated);
    }

    /**
     * Heartbeat to credit active duration safely.
     */
    public function heartbeat(int $id, Request $request): PracticeSessionResource
    {
        $session = PracticeSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $updated = $this->service->heartbeat($session, $request->user());

        return new PracticeSessionResource($updated);
    }

    /**
     * Update progress data safely with sequence ordering.
     */
    public function updateProgress(int $id, UpdatePracticeProgressRequest $request): PracticeSessionResource
    {
        $session = PracticeSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $updated = $this->service->updateProgress(
            $session,
            $request->user(),
            $request->validated('progress_data'),
            $request->validated('sequence')
        );

        return new PracticeSessionResource($updated);
    }

    /**
     * Complete a practice session.
     */
    public function complete(int $id, CompletePracticeSessionRequest $request): PracticeSessionResource
    {
        $session = PracticeSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $updated = $this->service->completeSession(
            $session,
            $request->user(),
            $request->validated('progress_data')
        );

        return new PracticeSessionResource($updated);
    }

    /**
     * Cancel an ongoing practice session.
     */
    public function cancel(int $id, Request $request): PracticeSessionResource
    {
        $session = PracticeSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $updated = $this->service->cancelSession($session, $request->user());

        return new PracticeSessionResource($updated);
    }
}
