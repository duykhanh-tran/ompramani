<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDecommissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'buyer@ompramani.local',
        ]);

        $category = ProductCategory::create([
            'name' => 'Pháp Khí',
            'slug' => 'phap-khi-test',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Chuông Xoay Tây Tạng',
            'slug' => 'chuong-xoay-tay-tang',
            'sku' => 'CX-001',
            'price_vnd' => 500000,
            'inventory_mode' => InventoryMode::Tracked,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
    }

    public function test_vnpay_payment_creation_is_strictly_rejected(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/shop/orders', [
            'customer_name' => 'Đạo Hữu An',
            'customer_phone' => '0987654321',
            'shipping_address' => 'Số 1 Phố Tuệ, Hà Nội',
            'shipping_province' => 'Hà Nội',
            'payment_method' => 'vnpay',
            'items' => [
                [
                    'product_id' => (string) $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_vnpay_routes_are_completely_removed(): void
    {
        $this->getJson('/api/v1/shop/vnpay/ipn')->assertStatus(404);
        $this->postJson('/api/v1/shop/vnpay/ipn')->assertStatus(404);
        $this->getJson('/api/v1/shop/vnpay/return')->assertStatus(404);
        $this->postJson('/api/v1/shop/vnpay/return')->assertStatus(404);
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/shop/orders/some-id/repay-vnpay')
            ->assertStatus(404);
    }

    public function test_vnpay_payment_service_is_removed(): void
    {
        $this->assertFalse(class_exists('App\\Services\\Shop\\VnpayPaymentService'));
        $this->assertFalse(class_exists('App\\Http\\Controllers\\Api\\V1\\ShopVnpayController'));
        $this->assertNull(config('services.vnpay'));
    }

    public function test_checkout_preview_only_exposes_cod_payment_method(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/shop/checkout/preview', [
            'items' => [
                [
                    'product_id' => (string) $this->product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertOk();
        $methods = $response->json('data.available_payment_methods');
        $codes = array_column($methods, 'code');
        $this->assertNotContains('vnpay', $codes);
        $this->assertContains('cod', $codes);
        $this->assertContains('sepay', $codes);
    }

    public function test_cod_checkout_creates_order_and_updates_inventory_safely(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/shop/orders', [
            'customer_name' => 'Đạo Hữu Tuệ',
            'customer_phone' => '0987654321',
            'shipping_address' => 'Chùa Quán Sứ, Hà Nội',
            'shipping_province' => 'Hà Nội',
            'payment_method' => 'cod',
            'items' => [
                [
                    'product_id' => (string) $this->product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $orderData = $response->json('data');

        $this->assertSame('cod', $orderData['payment_method']);
        $this->assertSame('unpaid', $orderData['payment_status']);
        $this->assertSame('pending', $orderData['order_status']);
        $this->assertArrayNotHasKey('payment_url', $response->json());

        // Inventory must be decremented from 10 to 8
        $this->assertSame(8, $this->product->fresh()->stock_quantity);

        // Inventory movement must be recorded
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'type' => 'order_placed',
            'change' => -2,
            'quantity_before' => 10,
            'quantity_after' => 8,
        ]);
    }

    public function test_historical_vnpay_order_and_payment_records_remain_readable(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'customer_name' => 'Đạo Hữu Cũ',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 1400000,
            'shipping_fee_vnd' => 0,
            'total_vnd' => 1400000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Vnpay,
            'payment_status' => PaymentStatus::Pending,
            'placed_at' => now()->subDays(5),
        ]);

        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Vnpay,
            'provider' => 'vnpay',
            'amount_vnd' => 1400000,
            'status' => PaymentStatus::Pending,
            'transaction_ref' => 'OPMTESTHISTORICAL',
            'vnp_txn_ref' => 'OPMTESTHISTORICAL',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/shop/me/orders/{$order->public_id}");

        $response->assertOk();
        $this->assertSame('vnpay', $response->json('data.payment_method'));
        $this->assertSame('VNPAY (phương thức cũ)', $response->json('data.payment_method_label'));
        $this->assertFalse($response->json('data.can_retry_payment'));
        $this->assertTrue($response->json('data.can_switch_to_cod'));
        $this->assertSame('OPMTESTHISTORICAL', $response->json('data.latest_payment.transaction_ref'));
    }

    public function test_switching_unpaid_order_to_cod_works(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'customer_name' => 'Khách Hàng',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 500000,
            'shipping_fee_vnd' => 0,
            'total_vnd' => 500000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Vnpay,
            'payment_status' => PaymentStatus::Pending,
            'placed_at' => now(),
        ]);

        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Vnpay,
            'provider' => 'vnpay',
            'amount_vnd' => 500000,
            'status' => PaymentStatus::Pending,
            'transaction_ref' => 'OPMTESTSWITCH',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/shop/orders/{$order->public_id}/switch-to-cod");

        $response->assertOk();
        $this->assertSame('cod', $order->fresh()->payment_method->value);
        $this->assertSame('unpaid', $order->fresh()->payment_status->value);
        $this->assertSame(PaymentStatus::Cancelled, $payment->fresh()->status);
    }
}
