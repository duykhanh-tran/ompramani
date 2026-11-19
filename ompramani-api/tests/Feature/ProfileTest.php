<?php

namespace Tests\Feature;

use App\Models\Tradition;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Guest cannot GET profile.
     */
    public function test_01_guest_cannot_get_profile(): void
    {
        $response = $this->getJson(route('api.v1.profile.show'));
        $response->assertStatus(401);
    }

    /**
     * 2. User can GET own profile.
     */
    public function test_02_user_can_get_own_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Trí Duy',
            'email' => 'triduy@example.com',
        ]);

        $tradition = Tradition::create([
            'name' => 'Bắc Truyền',
            'slug' => 'bac-truyen',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'dharma_name' => 'Tâm An',
            'date_of_birth' => '1995-05-15',
            'gender' => 'male',
            'preferred_tradition_id' => $tradition->id,
            'location' => 'Đà Nẵng',
            'bio' => 'Tu tập mỗi ngày.',
            'dharma_journey_started_at' => '2020-01-01',
        ]);

        $response = $this->actingAs($user)->getJson(route('api.v1.profile.show'));

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Trí Duy')
            ->assertJsonPath('data.email', 'triduy@example.com')
            ->assertJsonPath('data.profile.dharma_name', 'Tâm An')
            ->assertJsonPath('data.profile.gender', 'male')
            ->assertJsonPath('data.profile.location', 'Đà Nẵng')
            ->assertJsonPath('data.profile.preferred_tradition.name', 'Bắc Truyền');
    }

    /**
     * 3. User can create/update own profile.
     */
    public function test_03_user_can_create_and_update_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Cũ Tên']);

        $payload = [
            'name' => 'Mới Tên',
            'dharma_name' => 'Diệu Tâm',
            'date_of_birth' => '1998-12-25',
            'gender' => 'female',
            'location' => 'TP. Hồ Chí Minh',
            'bio' => 'Nguyện tinh tấn tu tập.',
            'dharma_journey_started_at' => '2021-06-01',
        ];

        $response = $this->actingAs($user)->patchJson(route('api.v1.profile.update'), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Mới Tên')
            ->assertJsonPath('data.profile.dharma_name', 'Diệu Tâm')
            ->assertJsonPath('data.profile.location', 'TP. Hồ Chí Minh');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Mới Tên',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'dharma_name' => 'Diệu Tâm',
            'location' => 'TP. Hồ Chí Minh',
        ]);
    }

    /**
     * 4. Profile update cannot escalate role.
     */
    public function test_04_profile_cannot_update_role(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->patchJson(route('api.v1.profile.update'), [
            'role' => 'admin',
            'dharma_name' => 'Hacker Name',
        ]);

        $response->assertStatus(200);
        $this->assertSame('user', $user->fresh()->role);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'user',
        ]);
    }

    /**
     * 5. Profile cannot update other user (session based only).
     */
    public function test_05_profile_cannot_update_other_user(): void
    {
        $user1 = User::factory()->create(['name' => 'Người Thứ Nhất']);
        $user2 = User::factory()->create(['name' => 'Người Thứ Hai']);

        $this->actingAs($user1)->patchJson(route('api.v1.profile.update'), [
            'user_id' => $user2->id,
            'name' => 'Đổi Tên',
            'dharma_name' => 'Tâm Không',
        ]);

        $this->assertSame('Đổi Tên', $user1->fresh()->name);
        $this->assertSame('Người Thứ Hai', $user2->fresh()->name);
        $this->assertNull($user2->fresh()->profile);
    }

    /**
     * 6. Dharma name Unicode preserved.
     */
    public function test_06_dharma_name_unicode_preserved(): void
    {
        $user = User::factory()->create();

        $dharmaName = 'Diệu Âm Trí Duy — 釋智維';

        $response = $this->actingAs($user)->patchJson(route('api.v1.profile.update'), [
            'dharma_name' => $dharmaName,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.profile.dharma_name', $dharmaName);

        $this->assertSame($dharmaName, $user->fresh()->profile->dharma_name);
    }

    /**
     * 7. Date of birth in future rejected with 422.
     */
    public function test_07_dob_future_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('api.v1.profile.update'), [
            'date_of_birth' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_of_birth']);
    }

    /**
     * 8. Valid tradition accepted.
     */
    public function test_08_valid_tradition_accepted(): void
    {
        $user = User::factory()->create();
        $tradition = Tradition::create([
            'name' => 'Nam Truyền Theravada',
            'slug' => 'nam-truyen',
        ]);

        $response = $this->actingAs($user)->patchJson(route('api.v1.profile.update'), [
            'preferred_tradition_id' => $tradition->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.profile.preferred_tradition.id', $tradition->id)
            ->assertJsonPath('data.profile.preferred_tradition.name', 'Nam Truyền Theravada');
    }

    /**
     * 9. Invalid tradition rejected with 422.
     */
    public function test_09_invalid_tradition_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('api.v1.profile.update'), [
            'preferred_tradition_id' => 999999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferred_tradition_id']);
    }

    /**
     * 10. Valid avatar upload.
     */
    public function test_10_avatar_valid_upload(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $response = $this->actingAs($user)->postJson(route('api.v1.profile.avatar.upload'), [
            'avatar' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['avatar', 'avatar_url', 'user'],
            ]);

        $avatarPath = $response->json('data.avatar');
        Storage::disk('public')->assertExists($avatarPath);
        $this->assertSame($avatarPath, $user->fresh()->profile->avatar);
    }

    /**
     * 11. Fake image rejected with 422.
     */
    public function test_11_fake_image_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('malicious.png', 50, 'text/plain');

        $response = $this->actingAs($user)->postJson(route('api.v1.profile.avatar.upload'), [
            'avatar' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    /**
     * 12. Oversized avatar rejected (> 5MB).
     */
    public function test_12_oversized_avatar_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // 6MB image
        $file = UploadedFile::fake()->image('large.jpg')->size(6144);

        $response = $this->actingAs($user)->postJson(route('api.v1.profile.avatar.upload'), [
            'avatar' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    /**
     * 13. Avatar replacement removes old file.
     */
    public function test_13_avatar_replacement_works(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // Upload first avatar
        $firstFile = UploadedFile::fake()->image('first.jpg');
        $firstRes = $this->actingAs($user)->postJson(route('api.v1.profile.avatar.upload'), [
            'avatar' => $firstFile,
        ]);
        $firstPath = $firstRes->json('data.avatar');
        Storage::disk('public')->assertExists($firstPath);

        // Upload second avatar
        $secondFile = UploadedFile::fake()->image('second.png');
        $secondRes = $this->actingAs($user)->postJson(route('api.v1.profile.avatar.upload'), [
            'avatar' => $secondFile,
        ]);
        $secondPath = $secondRes->json('data.avatar');

        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        $this->assertSame($secondPath, $user->fresh()->profile->avatar);
    }

    /**
     * 14. Avatar deletion works.
     */
    public function test_14_avatar_deletion_works(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('to_delete.webp');
        $uploadRes = $this->actingAs($user)->postJson(route('api.v1.profile.avatar.upload'), [
            'avatar' => $file,
        ]);
        $path = $uploadRes->json('data.avatar');
        Storage::disk('public')->assertExists($path);

        $delRes = $this->actingAs($user)->deleteJson(route('api.v1.profile.avatar.delete'));
        $delRes->assertStatus(200);

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($user->fresh()->profile->avatar);
    }

    /**
     * 15. File path not exposed as local FS path.
     */
    public function test_15_file_path_not_exposed_as_local_fs_path(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.jpg');
        $res = $this->actingAs($user)->postJson(route('api.v1.profile.avatar.upload'), [
            'avatar' => $file,
        ]);

        $avatar = $res->json('data.avatar');
        $avatarUrl = $res->json('data.avatar_url');

        $this->assertStringNotContainsString('F:\\', $avatar);
        $this->assertStringNotContainsString('C:\\', $avatar);
        $this->assertStringNotContainsString('F:\\', $avatarUrl);
        $this->assertStringNotContainsString('C:\\', $avatarUrl);
        $this->assertStringStartsWith('avatars/', $avatar);
    }

    /**
     * 16. Email update requires correct current password.
     */
    public function test_16_email_update_requires_password(): void
    {
        $user = User::factory()->create([
            'email' => 'oldemail@example.com',
            'password' => Hash::make('DungMatKhau123'),
        ]);

        // Wrong password
        $this->actingAs($user)->patchJson(route('api.v1.account.email.update'), [
            'email' => 'newemail@example.com',
            'current_password' => 'SaiMatKhau999',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        // Missing password
        $this->actingAs($user)->patchJson(route('api.v1.account.email.update'), [
            'email' => 'newemail@example.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /**
     * 17. Email update validates uniqueness.
     */
    public function test_17_email_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create([
            'email' => 'myemail@example.com',
            'password' => Hash::make('ChinhXac123'),
        ]);

        $this->actingAs($user)->patchJson(route('api.v1.account.email.update'), [
            'email' => 'taken@example.com',
            'current_password' => 'ChinhXac123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * 18. Email update clears email verification status.
     */
    public function test_18_email_update_clears_verification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('ChinhXac123'),
        ]);

        $res = $this->actingAs($user)->patchJson(route('api.v1.account.email.update'), [
            'email' => 'fresh@example.com',
            'current_password' => 'ChinhXac123',
        ]);

        $res->assertStatus(200);

        $freshUser = $user->fresh();
        $this->assertSame('fresh@example.com', $freshUser->email);
        $this->assertNull($freshUser->email_verified_at);
        $this->assertFalse($freshUser->hasVerifiedEmail());
    }

    /**
     * 19. Change password requires valid current password.
     */
    public function test_19_change_password_requires_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CuMatKhau123'),
        ]);

        $this->actingAs($user)->patchJson(route('api.v1.account.password.change'), [
            'current_password' => 'SaiCuKhau123',
            'password' => 'MoiMatKhau123',
            'password_confirmation' => 'MoiMatKhau123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /**
     * 20. Password gets properly hashed on change.
     */
    public function test_20_password_gets_hashed(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CuMatKhau123'),
        ]);

        $response = $this->actingAs($user)->patchJson(route('api.v1.account.password.change'), [
            'current_password' => 'CuMatKhau123',
            'password' => 'MoiMatKhau123',
            'password_confirmation' => 'MoiMatKhau123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Đổi mật khẩu thành công.');

        $freshUser = $user->fresh();
        $this->assertTrue(Hash::check('MoiMatKhau123', $freshUser->password));
        $this->assertFalse(Hash::check('CuMatKhau123', $freshUser->password));
    }
}
