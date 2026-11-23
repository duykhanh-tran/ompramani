<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Enforce role = 'user' regardless of input payload
        $user = User::create([
            'name' => (string) $request->string('name')->trim(),
            'email' => (string) $request->string('email')->lower()->trim(),
            'password' => Hash::make((string) $request->string('password')),
            'role' => 'user',
        ]);

        event(new Registered($user));

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $expiresAt = now()->addDays(7);
        $token = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'token' => $token,
            'user' => new UserResource($user),
        ], 201, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Handle user login.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        /** @var User $user */
        $user = $request->user();
        $remember = (bool) $request->boolean('remember');
        $expiresAt = $remember ? now()->addDays(30) : now()->addDays(7);
        $token = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'token' => $token,
            'user' => new UserResource($user),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            if ($request->user()->currentAccessToken() instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $request->user()->currentAccessToken()->delete();
            } elseif ($request->bearerToken()) {
                $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
                $tokenModel?->delete();
            }
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        Auth::forgetGuards();

        return response()->json([
            'message' => 'Đã đăng xuất thành công.',
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Chưa xác thực danh tính.',
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $userResource = new UserResource($user);

        return response()->json([
            'data' => $userResource,
            'user' => $userResource,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
