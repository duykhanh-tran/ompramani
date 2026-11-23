<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Registration succeeds with valid data.
     */
    public function test_01_user_can_register_with_valid_data(): void
    {
        $payload = [
            'name' => 'Nguyễn Văn An',
            'email' => 'nguyenvanan@example.com',
            'password' => 'MatKhau123',
            'password_confirmation' => 'MatKhau123',
        ];

        $response = $this->postJson(route('api.v1.auth.register'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.name', 'Nguyễn Văn An')
            ->assertJsonPath('data.user.email', 'nguyenvanan@example.com')
            ->assertJsonPath('data.user.role', 'user');

        $this->assertDatabaseHas('users', [
            'email' => 'nguyenvanan@example.com',
            'role' => 'user',
        ]);
    }

    /**
     * 2. Password is properly encrypted with bcrypt/argon2.
     */
    public function test_02_password_is_properly_hashed_on_registration(): void
    {
        $payload = [
            'name' => 'Trần Thị Bình',
            'email' => 'binh@example.com',
            'password' => 'MatKhau123',
            'password_confirmation' => 'MatKhau123',
        ];

        $this->postJson(route('api.v1.auth.register'), $payload)->assertStatus(201);

        $user = User::where('email', 'binh@example.com')->firstOrFail();

        $this->assertNotEquals('MatKhau123', $user->password);
        $this->assertTrue(Hash::check('MatKhau123', $user->password));
    }

    /**
     * 3. Registration strictly enforces role = 'user' even if 'admin' requested.
     */
    public function test_03_registration_enforces_user_role_even_if_admin_provided(): void
    {
        $payload = [
            'name' => 'Kẻ Xâm Nhập',
            'email' => 'hacker@example.com',
            'password' => 'MatKhau123',
            'password_confirmation' => 'MatKhau123',
            'role' => 'admin',
        ];

        $response = $this->postJson(route('api.v1.auth.register'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.role', 'user');

        $this->assertDatabaseHas('users', [
            'email' => 'hacker@example.com',
            'role' => 'user',
        ]);

        $user = User::where('email', 'hacker@example.com')->firstOrFail();
        $this->assertSame('user', $user->role);
    }

    /**
     * 4. Duplicate email registration is rejected with 422.
     */
    public function test_04_duplicate_email_registration_is_rejected(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $payload = [
            'name' => 'Người Dùng Khác',
            'email' => 'existing@example.com',
            'password' => 'MatKhau123',
            'password_confirmation' => 'MatKhau123',
        ];

        $response = $this->postJson(route('api.v1.auth.register'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * 5. Weak password registration is rejected with 422.
     */
    public function test_05_weak_password_registration_is_rejected(): void
    {
        // Too short (< 8 chars)
        $shortPayload = [
            'name' => 'Ngắn',
            'email' => 'short@example.com',
            'password' => '123a',
            'password_confirmation' => '123a',
        ];
        $this->postJson(route('api.v1.auth.register'), $shortPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // No numbers
        $noNumbersPayload = [
            'name' => 'Không Số',
            'email' => 'nonumbers@example.com',
            'password' => 'passwordonly',
            'password_confirmation' => 'passwordonly',
        ];
        $this->postJson(route('api.v1.auth.register'), $noNumbersPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * 6. Unconfirmed password registration is rejected with 422.
     */
    public function test_06_unconfirmed_password_is_rejected(): void
    {
        $payload = [
            'name' => 'Sai Khớp',
            'email' => 'mismatch@example.com',
            'password' => 'MatKhau123',
            'password_confirmation' => 'KhacKhau123',
        ];

        $response = $this->postJson(route('api.v1.auth.register'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * 7. User can login with correct credentials.
     */
    public function test_07_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'chinhxac@example.com',
            'password' => Hash::make('ChinhXac123'),
        ]);

        $payload = [
            'email' => 'chinhxac@example.com',
            'password' => 'ChinhXac123',
        ];

        $response = $this->postJson(route('api.v1.auth.login'), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.email', 'chinhxac@example.com')
            ->assertJsonPath('data.user.role', 'user');

        $this->assertAuthenticatedAs($user);
    }

    /**
     * 8. Login fails with incorrect password without revealing account existence.
     */
    public function test_08_login_fails_with_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'user8@example.com',
            'password' => Hash::make('DungMatKhau123'),
        ]);

        $payload = [
            'email' => 'user8@example.com',
            'password' => 'SaiMatKhau999',
        ];

        $response = $this->postJson(route('api.v1.auth.login'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    /**
     * 9. Login fails with nonexistent email with the same generic error message.
     */
    public function test_09_login_fails_with_nonexistent_email(): void
    {
        $payload = [
            'email' => 'khongtontai@example.com',
            'password' => 'MatKhau123',
        ];

        $response = $this->postJson(route('api.v1.auth.login'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    /**
     * 10. Login rate limiter triggers lockout after 5 failed attempts.
     */
    public function test_10_login_rate_limiter_locks_out_after_five_failed_attempts(): void
    {
        $payload = [
            'email' => 'ratelimit@example.com',
            'password' => 'SaiMatKhau123',
        ];

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.v1.auth.login'), $payload)->assertStatus(422);
        }

        // 6th attempt should be blocked with 429 Too Many Requests
        $response = $this->postJson(route('api.v1.auth.login'), $payload);
        $response->assertStatus(429);
    }

    /**
     * 11. Login regenerates session.
     */
    public function test_11_login_regenerates_session(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('ChinhXac123'),
        ]);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'ChinhXac123',
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * 12. GET /me returns authenticated user without sensitive fields.
     */
    public function test_12_me_endpoint_returns_authenticated_user_without_sensitive_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Thích Nhật Từ',
            'email' => 'thichnhattu@example.com',
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->getJson(route('api.v1.auth.me'));

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Thích Nhật Từ')
            ->assertJsonPath('data.email', 'thichnhattu@example.com')
            ->assertJsonPath('data.role', 'user')
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonMissing(['password'])
            ->assertJsonMissing(['remember_token']);
    }

    /**
     * 13. GET /me returns 401 for unauthenticated request.
     */
    public function test_13_me_endpoint_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->getJson(route('api.v1.auth.me'));

        $response->assertStatus(401);
    }

    /**
     * 14. Logout invalidates session and clears authentication.
     */
    public function test_14_logout_invalidates_session_and_clears_auth(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('api.v1.auth.logout'));

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Đã đăng xuất thành công.');

        $this->assertGuest();
    }

    /**
     * 15. Unauthenticated user cannot access protected endpoints.
     */
    public function test_15_unauthenticated_user_cannot_call_protected_endpoints(): void
    {
        $this->postJson(route('api.v1.auth.logout'))->assertStatus(401);
        $this->postJson(route('api.v1.auth.email.resend'))->assertStatus(401);
        $this->getJson(route('api.v1.auth.email.status'))->assertStatus(401);
    }

    /**
     * 16. Forgot password returns generic success message for existing email.
     */
    public function test_16_forgot_password_returns_generic_success_for_existing_email(): void
    {
        User::factory()->create(['email' => 'valid@example.com']);

        $response = $this->postJson(route('api.v1.auth.forgot-password'), [
            'email' => 'valid@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Nếu email tồn tại trong hệ thống, hướng dẫn đặt lại mật khẩu sẽ được gửi.');
    }

    /**
     * 17. Forgot password returns generic success message for nonexistent email (anti-enumeration).
     */
    public function test_17_forgot_password_returns_generic_success_for_nonexistent_email(): void
    {
        $response = $this->postJson(route('api.v1.auth.forgot-password'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Nếu email tồn tại trong hệ thống, hướng dẫn đặt lại mật khẩu sẽ được gửi.');
    }

    /**
     * 18. User can reset password with valid token.
     */
    public function test_18_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'resetuser@example.com',
            'password' => Hash::make('CuPass123'),
        ]);

        $token = Password::createToken($user);

        $payload = [
            'token' => $token,
            'email' => 'resetuser@example.com',
            'password' => 'MoiPass123',
            'password_confirmation' => 'MoiPass123',
        ];

        $response = $this->postJson(route('api.v1.auth.reset-password'), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập ngay.');

        $this->assertTrue(Hash::check('MoiPass123', $user->fresh()->password));
    }

    /**
     * 19. Reset password fails with invalid token.
     */
    public function test_19_reset_password_fails_with_invalid_token(): void
    {
        User::factory()->create(['email' => 'resetbad@example.com']);

        $payload = [
            'token' => 'invalid-token-xyz',
            'email' => 'resetbad@example.com',
            'password' => 'MoiPass123',
            'password_confirmation' => 'MoiPass123',
        ];

        $response = $this->postJson(route('api.v1.auth.reset-password'), $payload);

        $response->assertStatus(422);
    }

    /**
     * 20. Filament panel access is strictly denied for 'user' and allowed for 'admin'.
     */
    public function test_20_filament_panel_access_is_enforced_by_role(): void
    {
        $regularUser = User::factory()->create(['role' => 'user']);
        $adminUser = User::factory()->admin()->create();

        $panel = Filament::getCurrentOrDefaultPanel();

        $this->assertFalse($regularUser->canAccessPanel($panel));
        $this->assertTrue($adminUser->canAccessPanel($panel));
    }

    /**
     * 21. User can verify email with valid signed route.
     */
    public function test_21_user_can_verify_email_with_valid_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Email đã được xác thực thành công.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    /**
     * 22. Email verification fails with invalid hash.
     */
    public function test_22_email_verification_fails_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'invalid-hash']
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    /**
     * 23. Authenticated user can resend verification notification.
     */
    public function test_23_authenticated_user_can_resend_verification_notification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->postJson(route('api.v1.auth.email.resend'));

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Đã gửi liên kết xác thực email.');

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    /**
     * 24. Authenticated user can check email verification status.
     */
    public function test_24_authenticated_user_can_check_email_status(): void
    {
        $unverifiedUser = User::factory()->unverified()->create();
        $this->actingAs($unverifiedUser)
            ->getJson(route('api.v1.auth.email.status'))
            ->assertStatus(200)
            ->assertJsonPath('data.email_verified', false);

        $verifiedUser = User::factory()->create();
        $this->actingAs($verifiedUser)
            ->getJson(route('api.v1.auth.email.status'))
            ->assertStatus(200)
            ->assertJsonPath('data.email_verified', true);
    }

    /**
     * 25. Complete token-based authentication flow for cross-origin SPAs.
     */
    public function test_25_token_based_authentication_flow(): void
    {
        $user = User::factory()->create([
            'email' => 'tokenuser@example.com',
            'password' => Hash::make('SecretPass123'),
        ]);

        // Login returns plainTextToken
        $loginRes = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'tokenuser@example.com',
            'password' => 'SecretPass123',
        ]);

        $loginRes->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'email'],
                    'token',
                ],
                'token',
            ]);

        $token = $loginRes->json('data.token');
        $this->assertNotEmpty($token);

        // Stateless request with Authorization header works on protected route
        $meRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route('api.v1.auth.me'));

        $meRes->assertStatus(200)
            ->assertJsonPath('data.email', 'tokenuser@example.com');

        // Logout with Bearer token revokes the token
        $logoutRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.v1.auth.logout'));

        $logoutRes->assertStatus(200);

        // Token is revoked from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        // Stateless request without session returns 401
        $this->flushSession();
        $afterLogoutRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route('api.v1.auth.me'));

        $afterLogoutRes->assertStatus(401);
    }
}
