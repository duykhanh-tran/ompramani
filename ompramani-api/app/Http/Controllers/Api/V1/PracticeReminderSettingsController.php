<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Practice\UpdatePracticeReminderSettingsRequest;
use App\Http\Resources\Api\V1\PracticeReminderSettingsResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PracticeReminderSettingsController extends Controller
{
    /**
     * Show effective practice reminder and quiet hours settings for the authenticated user.
     */
    public function show(Request $request): JsonResponse
    {
        $preferences = $request->user()->getEffectiveNotificationPreferences();

        return (new PracticeReminderSettingsResource($preferences))->response()->setStatusCode(200);
    }

    /**
     * Update practice reminder and quiet hours settings for the authenticated user.
     */
    public function update(UpdatePracticeReminderSettingsRequest $request): JsonResponse
    {
        $preferences = $request->user()->getEffectiveNotificationPreferences();
        $preferences->update($request->validated());

        return (new PracticeReminderSettingsResource($preferences))->response()->setStatusCode(200);
    }
}
