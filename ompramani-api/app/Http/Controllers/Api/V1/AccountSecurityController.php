<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Account\UpdateEmailRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AccountSecurityController extends Controller
{
    /**
     * Update user email address and re-trigger email verification.
     */
    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $newEmail = (string) $request->string('email')->lower()->trim();

        if ($newEmail === $user->email) {
            return response()->json([
                'message' => 'Địa chỉ email không thay đổi.',
                'data' => new UserResource($user->load('profile.preferredTradition')),
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $user->email = $newEmail;
        $user->email_verified_at = null;
        $user->save();

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Đã cập nhật email thành công. Vui lòng kiểm tra hòm thư để xác thực địa chỉ email mới.',
            'data' => new UserResource($user->load('profile.preferredTradition')),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Change user password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->password = Hash::make((string) $request->string('password'));
        $user->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công.',
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
