<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Mark the user's email address as verified.
     */
    public function verify(Request $request, int|string $id, string $hash): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = User::find($id);

        if (! $user) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Người dùng không tồn tại.',
                ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            return redirect(config('app.frontend_url', 'http://localhost:3000').'/xac-thuc-email?error=user_not_found');
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Liên kết xác thực không hợp lệ.',
                ], 403, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            return redirect(config('app.frontend_url', 'http://localhost:3000').'/xac-thuc-email?error=invalid_hash');
        }

        if ($user->hasVerifiedEmail()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Email đã được xác thực trước đó.',
                ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            return redirect(config('app.frontend_url', 'http://localhost:3000').'/xac-thuc-email?verified=already');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Email đã được xác thực thành công.',
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return redirect(config('app.frontend_url', 'http://localhost:3000').'/xac-thuc-email?verified=success');
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email đã được xác thực trước đó.',
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Đã gửi liên kết xác thực email.',
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get email verification status for current user.
     */
    public function status(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'email' => $user->email,
                'email_verified' => $user->hasVerifiedEmail(),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
