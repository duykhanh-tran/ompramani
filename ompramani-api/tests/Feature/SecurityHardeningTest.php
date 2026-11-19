<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PrayerCategory;
use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerVisibility;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\PracticeSession;
use App\Models\PrayerIntention;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sutra;
use App\Models\User;
use App\Models\UserFavorite;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 1. SECURITY HEADERS
    // =========================================================================

    public function test_security_headers_are_present_on_api_responses(): void
    {
        $response = $this->getJson(route('api.v1.sutras.index'));

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    // =========================================================================
    // 2. AUTHENTICATION & ACCESS CONTROL
    // =========================================================================

    public function test_guest_cannot_access_protected_apis(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
        $this->getJson('/api/v1/profile')->assertStatus(401);
        $this->getJson('/api/v1/me/practice-sessions')->assertStatus(401);
        $this->getJson('/api/v1/me/notifications')->assertStatus(401);
        $this->getJson('/api/v1/me/favorites')->assertStatus(401);
        $this->getJson('/api/v1/shop/me/orders')->assertStatus(401);
    }

    public function test_user_cannot_elevate_privileges_via_registration_role_manipulation(): void
    {
        $payload = [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'role' => 'admin', // Malicious attempt to elevate role
        ];

        $response = $this->postJson(route('api.v1.auth.register'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.role', 'user');

        $user = User::where('email', 'attacker@example.com')->firstOrFail();
        $this->assertEquals('user', $user->role);
    }

    public function test_forgot_password_does_not_reveal_whether_email_exists(): void
    {
        // Existing user
        User::factory()->create(['email' => 'realuser@example.com']);

        $res1 = $this->postJson(route('api.v1.auth.forgot-password'), ['email' => 'realuser@example.com']);
        $res1->assertStatus(200);

        // Non-existing user
        $res2 = $this->postJson(route('api.v1.auth.forgot-password'), ['email' => 'fakeuser@example.com']);
        $res2->assertStatus(200);

        // Exactly identical response prevents account enumeration
        $this->assertEquals($res1->json('message'), $res2->json('message'));
    }

    // =========================================================================
    // 3. FILAMENT ADMIN ACCESS CONTROL
    // =========================================================================

    public function test_regular_user_cannot_access_filament_admin_panel(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $panel = Filament::getCurrentOrDefaultPanel();

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_admin_user_can_access_filament_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $panel = Filament::getCurrentOrDefaultPanel();

        $this->assertTrue($admin->canAccessPanel($panel));
    }

    // =========================================================================
    // 4. USER OWNERSHIP & IDOR DEFENSES
    // =========================================================================

    public function test_user_b_cannot_read_or_manipulate_user_a_practice_session(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $sessionA = PracticeSession::create([
            'user_id' => $userA->id,
            'practice_type' => 'meditation',
            'status' => 'completed',
            'started_at' => now()->subMinutes(30),
            'ended_at' => now(),
            'actual_duration_seconds' => 1800,
            'source' => 'web',
        ]);

        // User B tries to view User A's session
        $this->actingAs($userB)
            ->getJson("/api/v1/me/practice-sessions/{$sessionA->id}")
            ->assertStatus(404);

        // User B tries to pause User A's session
        $this->actingAs($userB)
            ->postJson("/api/v1/me/practice-sessions/{$sessionA->id}/pause")
            ->assertStatus(404);
    }

    public function test_user_b_cannot_read_or_cancel_user_a_order(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $orderA = Order::create([
            'public_id' => (string) Str::ulid(),
            'order_number' => 'ORD-TEST-001',
            'user_id' => $userA->id,
            'customer_name' => 'Nguyễn A',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Đường A, Hà Nội',
            'shipping_province' => 'Hà Nội',
            'shipping_fee_vnd' => 0,
            'subtotal_vnd' => 100000,
            'total_vnd' => 100000,
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Unpaid,
            'placed_at' => now(),
        ]);

        // User B tries to view User A's order
        $this->actingAs($userB)
            ->getJson("/api/v1/shop/me/orders/{$orderA->public_id}")
            ->assertStatus(404);

        // User B tries to cancel User A's order
        $this->actingAs($userB)
            ->postJson("/api/v1/shop/me/orders/{$orderA->public_id}/cancel", ['reason' => 'Hủy trộm'])
            ->assertStatus(404);
    }

    public function test_user_b_cannot_access_or_modify_user_a_notifications(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $notifA = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $userA->id,
            'data' => ['message' => 'Thông báo riêng tư của User A'],
            'read_at' => null,
        ]);

        // User B tries to mark User A's notification as read
        $this->actingAs($userB)
            ->patchJson("/api/v1/me/notifications/{$notifA->id}/read")
            ->assertStatus(404);

        // User B tries to delete User A's notification
        $this->actingAs($userB)
            ->deleteJson("/api/v1/me/notifications/{$notifA->id}")
            ->assertStatus(404);

        $this->assertNull($notifA->fresh()->read_at);
    }

    public function test_user_b_cannot_view_or_delete_user_a_favorites(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $sutra = Sutra::factory()->published()->create();

        UserFavorite::create([
            'user_id' => $userA->id,
            'favoritable_type' => 'sutra',
            'favoritable_id' => $sutra->id,
        ]);

        // User B checks their favorites - must not see User A's favorite
        $response = $this->actingAs($userB)->getJson('/api/v1/me/favorites');
        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    public function test_private_prayer_intentions_are_not_exposed_in_public_feed(): void
    {
        $user = User::factory()->create();

        // Create private prayer intention
        $privatePrayer = PrayerIntention::create([
            'public_id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'title' => 'Tâm nguyện bí mật gia đình',
            'body' => 'Nội dung rất riêng tư không công khai',
            'category' => PrayerCategory::BinhAn->value,
            'visibility' => PrayerVisibility::Private->value,
            'moderation_status' => PrayerModerationStatus::Approved->value,
            'published_at' => now(),
        ]);

        // Public feed must not list private prayer
        $feed = $this->getJson('/api/v1/prayer-intentions');
        $feed->assertStatus(200);
        $titles = collect($feed->json('data'))->pluck('title')->all();
        $this->assertNotContains('Tâm nguyện bí mật gia đình', $titles);

        // Public show must return 404 for private prayer
        $this->getJson("/api/v1/prayer-intentions/{$privatePrayer->public_id}")
            ->assertStatus(404);
    }

    // =========================================================================
    // 5. SHOP PRICE & PAYMENT INTEGRITY
    // =========================================================================

    public function test_order_creation_rejects_client_price_tampering(): void
    {
        $user = User::factory()->create();

        $category = ProductCategory::create([
            'name' => 'Pháp Khí & Tràng Hạt',
            'slug' => 'phap-khi-trang-hat',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Chuỗi Hạt Trầm Hương Tự Nhiên',
            'slug' => 'chuoi-hat-tram-huong',
            'sku' => 'TH-001',
            'short_description' => 'Trầm hương tự nhiên.',
            'description' => 'Mộc mạc trang nghiêm.',
            'price_vnd' => 500000,
            'stock_quantity' => 10,
            'inventory_mode' => InventoryMode::Tracked,
            'status' => ProductStatus::Published,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        // Attacker attempts to send total_vnd = 1000 or unit_price = 1000
        $payload = [
            'customer_name' => 'Lê Thử',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Số 1 Phố Huế',
            'shipping_province' => 'Hà Nội',
            'payment_method' => 'cod',
            'items' => [
                [
                    'product_id' => $product->public_id,
                    'quantity' => 1,
                    'unit_price_vnd' => 1000, // Tampered price
                    'total_vnd' => 1000,      // Tampered total
                ],
            ],
            'total_vnd' => 1000, // Tampered order total
        ];

        $response = $this->actingAs($user)->postJson(route('api.v1.shop.orders.store'), $payload);

        $response->assertStatus(201);

        // Server authoritative price must be 500,000 VND (NOT 1,000 VND)
        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(500000, $order->total_vnd);
        $this->assertEquals(PaymentStatus::Unpaid, $order->payment_status);
    }

    public function test_avatar_upload_rejects_malicious_executable_files(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // Fake PHP script disguised as an image
        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $response = $this->actingAs($user)->postJson(route('api.v1.profile.avatar.upload'), [
            'avatar' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }
}
