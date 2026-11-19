<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Prayer\StorePrayerResponseRequest;
use App\Http\Resources\Api\V1\MyPrayerResponseResource;
use App\Http\Resources\Api\V1\PublicPrayerResponseResource;
use App\Services\Prayer\PrayerResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicPrayerResponseController extends Controller
{
    public function __construct(
        protected PrayerResponseService $responseService
    ) {}

    /**
     * List public approved responses for a public prayer intention.
     */
    public function index(Request $request, string $publicId): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $responses = $this->responseService->getPublicResponses($publicId, $perPage);

        return PublicPrayerResponseResource::collection($responses);
    }

    /**
     * Submit a supportive response to a public prayer intention (Authenticated).
     * Response is created in 'pending' moderation status.
     */
    public function store(StorePrayerResponseRequest $request, string $publicId): JsonResponse
    {
        $response = $this->responseService->createResponse(
            $request->user(),
            $publicId,
            (string) $request->validated('body'),
            $request->validated('client_request_id')
        );

        return (new MyPrayerResponseResource($response))
            ->response()
            ->setStatusCode(201);
    }
}
