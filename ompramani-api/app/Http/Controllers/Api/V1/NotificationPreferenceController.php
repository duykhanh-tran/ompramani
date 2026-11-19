<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\UpdateNotificationPreferencesRequest;
use App\Http\Resources\Api\V1\NotificationPreferenceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * Show effective notification preferences for the authenticated user.
     */
    public function show(Request $request): JsonResponse
    {
        $preferences = $request->user()->getEffectiveNotificationPreferences();

        return (new NotificationPreferenceResource($preferences))->response()->setStatusCode(200);
    }

    /**
     * Update notification preferences for the authenticated user.
     */
    public function update(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $preferences = $request->user()->getEffectiveNotificationPreferences();
        $preferences->update($request->validated());

        return (new NotificationPreferenceResource($preferences))->response()->setStatusCode(200);
    }
}
