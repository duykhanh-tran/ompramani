<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Profile\UploadAvatarRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('profile.preferredTradition');

        return response()->json([
            'data' => new UserResource($user),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // 1. Update User basic attributes if provided
        if ($request->has('name')) {
            $user->name = (string) $request->string('name')->trim();
            $user->save();
        }

        // 2. Ensure profile exists (lazy create)
        /** @var UserProfile $profile */
        $profile = $user->profile()->firstOrCreate([]);

        // 3. Update profile fields (whitelisted only)
        $profileData = $request->only([
            'dharma_name',
            'date_of_birth',
            'gender',
            'preferred_tradition_id',
            'location',
            'bio',
            'dharma_journey_started_at',
            'timezone',
        ]);

        if (array_key_exists('dharma_name', $profileData) && is_string($profileData['dharma_name'])) {
            $profileData['dharma_name'] = trim($profileData['dharma_name']);
        }
        if (array_key_exists('location', $profileData) && is_string($profileData['location'])) {
            $profileData['location'] = trim($profileData['location']);
        }
        if (array_key_exists('timezone', $profileData) && is_string($profileData['timezone'])) {
            try {
                $profileData['timezone'] = (new \DateTimeZone(trim($profileData['timezone'])))->getName();
            } catch (\Throwable) {
                // Keep validated value
                $profileData['timezone'] = trim($profileData['timezone']);
            }
        }

        $profile->update($profileData);

        $user->load('profile.preferredTradition');

        return response()->json([
            'message' => 'Đã cập nhật hồ sơ thành công.',
            'data' => new UserResource($user),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Upload and update user avatar.
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var UserProfile $profile */
        $profile = $user->profile()->firstOrCreate([]);

        $file = $request->file('avatar');
        $oldAvatar = $profile->avatar;

        // Store with randomized hash name in public avatars directory
        $newPath = $file->store('avatars', 'public');

        if (! $newPath) {
            return response()->json([
                'message' => 'Không thể lưu hình ảnh đại diện.',
            ], 500, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $profile->update([
            'avatar' => $newPath,
        ]);

        // Clean up old avatar if exists
        if ($oldAvatar && Storage::disk('public')->exists($oldAvatar)) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $user->load('profile.preferredTradition');

        return response()->json([
            'message' => 'Đã cập nhật ảnh đại diện thành công.',
            'data' => [
                'avatar' => $newPath,
                'avatar_url' => Storage::disk('public')->url($newPath),
                'user' => new UserResource($user),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Delete user avatar.
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $user->profile;

        if ($profile && $profile->avatar) {
            $oldAvatar = $profile->avatar;
            $profile->update([
                'avatar' => null,
            ]);

            if (Storage::disk('public')->exists($oldAvatar)) {
                Storage::disk('public')->delete($oldAvatar);
            }
        }

        $user->load('profile.preferredTradition');

        return response()->json([
            'message' => 'Đã xóa ảnh đại diện.',
            'data' => [
                'avatar' => null,
                'avatar_url' => null,
                'user' => new UserResource($user),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
