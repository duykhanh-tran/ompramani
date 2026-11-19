<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function createCategory(array $attributes = []): ProductCategory
    {
        return ProductCategory::create(array_merge([
            'name' => 'Tràng hạt & Pháp khí',
            'slug' => 'trang-hat-phap-khi',
            'sort_order' => 1,
            'is_active' => true,
        ], $attributes));
    }

    protected function createProduct(ProductCategory $category, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Chuỗi Bồ Đề 108 Hạt',
            'slug' => 'sp-'.Str::random(8),
            'sku' => 'BD-'.Str::random(8),
            'short_description' => 'Chuỗi hạt bồ đề tự nhiên.',
            'description' => 'Chất liệu hạt bồ đề tự nhiên mộc mạc.',
            'price_vnd' => 150000,
            'stock_quantity' => 20,
            'inventory_mode' => InventoryMode::Tracked,
            'status' => ProductStatus::Published,
            'is_active' => true,
            'cover_image' => 'products/chuoi-bo-de.jpg',
            'published_at' => now()->subDay(),
        ], $attributes));
    }

    protected function createUser(): User
    {
        return User::factory()->create([
            'name' => 'Phật Tử Giác Ngộ',
            'email' => 'giacngo_'.Str::random(5).'@ompramani.test',
        ]);
    }

    public function test_guest_cannot_preview_checkout_or_create_order(): void
    {
        $this->postJson('/api/v1/shop/checkout/preview', [
            'items' => [['product_id' => 'abc', 'quantity' => 1]],
        ])->assertStatus(401);

        $this->postJson('/api/v1/shop/orders', [
            'customer_name' => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Đường Phật Pháp',
            'shipping_province' => 'TP. Hồ Chí Minh',
            'payment_method' => 'cod',
            'items' => [['product_id' => 'abc', 'quantity' => 1]],
        ])->assertStatus(401);
    }

    public function test_authenticated_user_can_preview_checkout_with_shipping_fee(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $category = $this->createCategory();

        // 1. Order below 500k VND gets flat shipping fee 30k VND
        $product1 = $this->createProduct($category, ['price_vnd' => 200000]);

        $res1 = $this->postJson('/api/v1/shop/checkout/preview', [
            'items' => [['product_id' => $product1->public_id, 'quantity' => 1]],
        ]);

        $res1->assertOk()
            ->assertJsonPath('data.subtotal_vnd', 200000)
            ->assertJsonPath('data.shipping_fee_vnd', 30000)
            ->assertJsonPath('data.total_vnd', 230000)
            ->assertJsonPath('data.formatted_shipping_fee', '30.000 ₫');

        // 2. Order >= 500k VND gets free shipping (0 VND)
        $product2 = $this->createProduct($category, ['price_vnd' => 600000]);

        $res2 = $this->postJson('/api/v1/shop/checkout/preview', [
            'items' => [['product_id' => $product2->public_id, 'quantity' => 1]],
        ]);

        $res2->assertOk()
            ->assertJsonPath('data.subtotal_vnd', 600000)
            ->assertJsonPath('data.shipping_fee_vnd', 0)
            ->assertJsonPath('data.total_vnd', 600000)
            ->assertJsonPath('data.formatted_shipping_fee', 'Miễn phí');
    }

    public function test_authenticated_user_can_create_valid_cod_order(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $category = $this->createCategory();
        $product = $this->createProduct($category, [
            'name' => 'Mõ Gỗ Mít Tụng Kinh',
            'price_vnd' => 250000,
            'stock_quantity' => 10,
            'inventory_mode' => InventoryMode::Tracked,
        ]);

        $payload = [
            'customer_name' => 'Nguyễn Thanh Tùng',
            'customer_phone' => '0912345678',
            'customer_email' => 'tung@example.com',
            'shipping_address' => 'Số 45 Đường Giải Phóng',
            'shipping_province' => 'Hà Nội',
            'notes' => 'Giao vào buổi sáng giúp tôi.',
            'payment_method' => 'cod',
            'idempotency_key' => 'idem_'.Str::random(16),
            'items' => [
                ['product_id' => $product->public_id, 'quantity' => 2],
            ],
        ];

        $response = $this->postJson('/api/v1/shop/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer_name', 'Nguyễn Thanh Tùng')
            ->assertJsonPath('data.customer_phone', '0912345678')
            ->assertJsonPath('data.shipping_province', 'Hà Nội')
            ->assertJsonPath('data.subtotal_vnd', 500000)
            ->assertJsonPath('data.shipping_fee_vnd', 0) // Free shipping for >= 500k
            ->assertJsonPath('data.total_vnd', 500000)
            ->assertJsonPath('data.order_status', OrderStatus::Pending->value)
            ->assertJsonPath('data.payment_method', PaymentMethod::Cod->value)
            ->assertJsonPath('data.payment_status', PaymentStatus::Unpaid->value)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_name', 'Mõ Gỗ Mít Tụng Kinh')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.line_total_vnd', 500000);

        // Verify tracked stock was decremented from 10 to 8
        $this->assertEquals(8, $product->fresh()->stock_quantity);

        // Verify database records
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'customer_name' => 'Nguyễn Thanh Tùng',
            'payment_status' => 'unpaid',
            'total_vnd' => 500000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name_snapshot' => 'Mõ Gỗ Mít Tụng Kinh',
            'quantity' => 2,
            'unit_price_vnd' => 250000,
        ]);
    }

    public function test_checkout_rejects_insufficient_stock_for_tracked_products(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $category = $this->createCategory();
        $product = $this->createProduct($category, [
            'stock_quantity' => 3,
            'inventory_mode' => InventoryMode::Tracked,
        ]);

        $payload = [
            'customer_name' => 'Trần Văn B',
            'customer_phone' => '0987654321',
            'shipping_address' => '789 Đường Nam Kỳ Khởi Nghĩa',
            'shipping_province' => 'TP. Hồ Chí Minh',
            'payment_method' => 'cod',
            'items' => [
                ['product_id' => $product->public_id, 'quantity' => 5], // requesting 5 when only 3 available
            ],
        ];

        $response = $this->postJson('/api/v1/shop/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');

        // Verify stock was not consumed
        $this->assertEquals(3, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_idempotency_prevents_duplicate_orders_and_stock_decrements(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $category = $this->createCategory();
        $product = $this->createProduct($category, [
            'stock_quantity' => 10,
            'price_vnd' => 100000,
            'inventory_mode' => InventoryMode::Tracked,
        ]);

        $idempotencyKey = 'unique_key_12345';
        $payload = [
            'customer_name' => 'Lê Thị C',
            'customer_phone' => '0909090909',
            'shipping_address' => 'Số 10 Đường Hoàng Diệu',
            'shipping_province' => 'Đà Nẵng',
            'payment_method' => 'cod',
            'idempotency_key' => $idempotencyKey,
            'items' => [
                ['product_id' => $product->public_id, 'quantity' => 2],
            ],
        ];

        // First attempt creates order
        $res1 = $this->postJson('/api/v1/shop/orders', $payload);
        $res1->assertStatus(201);
        $orderNumber1 = $res1->json('data.order_number');

        // Stock decremented by 2 -> 8
        $this->assertEquals(8, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('orders', 1);

        // Second duplicate submit with same idempotency_key
        $res2 = $this->postJson('/api/v1/shop/orders', $payload);
        $res2->assertStatus(200);
        $this->assertEquals($orderNumber1, $res2->json('data.order_number'));

        // Stock MUST remain 8 (not decremented twice!)
        $this->assertEquals(8, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_user_cannot_access_another_users_order(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $category = $this->createCategory();
        $product = $this->createProduct($category);

        // User A creates an order
        Sanctum::actingAs($userA);
        $resA = $this->postJson('/api/v1/shop/orders', [
            'customer_name' => 'User A',
            'customer_phone' => '0901111111',
            'shipping_address' => 'Địa chỉ của A',
            'shipping_province' => 'Hà Nội',
            'payment_method' => 'cod',
            'items' => [['product_id' => $product->public_id, 'quantity' => 1]],
        ]);
        $resA->assertStatus(201);
        $orderPublicId = $resA->json('data.public_id');

        // User A can access their own order
        $this->getJson("/api/v1/shop/me/orders/{$orderPublicId}")
            ->assertOk()
            ->assertJsonPath('data.customer_name', 'User A');

        // User B attempts to access User A's order -> 404 forbidden
        Sanctum::actingAs($userB);
        $this->getJson("/api/v1/shop/me/orders/{$orderPublicId}")
            ->assertStatus(404);
    }

    public function test_order_creation_never_creates_practice_session(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $category = $this->createCategory();
        $product = $this->createProduct($category);

        $this->postJson('/api/v1/shop/orders', [
            'customer_name' => 'Phật tử an nhiên',
            'customer_phone' => '0902222222',
            'shipping_address' => 'Chùa An Lạc',
            'shipping_province' => 'Thừa Thiên Huế',
            'payment_method' => 'cod',
            'items' => [['product_id' => $product->public_id, 'quantity' => 1]],
        ])->assertStatus(201);

        // Strict domain separation: No practice sessions, no streak
        $this->assertDatabaseCount('practice_sessions', 0);
    }

    public function test_order_snapshots_remain_intact_when_product_changes_later(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $category = $this->createCategory();
        $product = $this->createProduct($category, [
            'name' => 'Tên Ban Đầu',
            'price_vnd' => 100000,
        ]);

        $res = $this->postJson('/api/v1/shop/orders', [
            'customer_name' => 'Người mua',
            'customer_phone' => '0903333333',
            'shipping_address' => 'Địa chỉ',
            'shipping_province' => 'Cần Thơ',
            'payment_method' => 'cod',
            'items' => [['product_id' => $product->public_id, 'quantity' => 1]],
        ]);
        $res->assertStatus(201);
        $orderPublicId = $res->json('data.public_id');

        // Admin modifies product name and raises price
        $product->update([
            'name' => 'Tên Đã Đổi Hoàn Toàn',
            'price_vnd' => 500000,
        ]);

        // Historical order still retains original name and price snapshot
        $orderRes = $this->getJson("/api/v1/shop/me/orders/{$orderPublicId}");
        $orderRes->assertOk()
            ->assertJsonPath('data.items.0.product_name', 'Tên Ban Đầu')
            ->assertJsonPath('data.items.0.unit_price_vnd', 100000)
            ->assertJsonPath('data.subtotal_vnd', 100000);
    }
}
