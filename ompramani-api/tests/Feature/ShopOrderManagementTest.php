<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PracticeSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Notifications\ShopOrderNotification;
use App\Services\Shop\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShopOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = ProductCategory::create([
            'name' => 'Kinh Sách & Pháp Khí',
            'slug' => 'kinh-sach-phap-khi',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createTrackedProduct(string $name, int $price, int $stock = 10): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => str()->slug($name).'-'.strtolower(str()->random(6)),
            'sku' => 'SKU-'.strtoupper(str()->random(6)),
            'price_vnd' => $price,
            'inventory_mode' => InventoryMode::Tracked,
            'stock_quantity' => $stock,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
    }

    private function createOrderForUser(User $user, OrderStatus $status = OrderStatus::Pending, ?Product $product = null, int $quantity = 2): Order
    {
        $prod = $product ?? $this->createTrackedProduct('Chuông Xoay Tây Tạng', 300000, 10);
        $subtotal = $prod->price_vnd * $quantity;
        $shippingFee = $subtotal >= 500000 ? 0 : 30000;
        $total = $subtotal + $shippingFee;

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0912345678',
            'customer_email' => $user->email,
            'shipping_address' => 'Số 10 Chùa Quán Sứ',
            'shipping_province' => 'Hà Nội',
            'shipping_fee_vnd' => $shippingFee,
            'subtotal_vnd' => $subtotal,
            'total_vnd' => $total,
            'currency' => 'VND',
            'order_status' => $status,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Unpaid,
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $prod->id,
            'product_public_id' => $prod->public_id,
            'product_name_snapshot' => $prod->name,
            'product_sku_snapshot' => $prod->sku,
            'product_image_snapshot' => $prod->cover_image,
            'unit_price_vnd' => $prod->price_vnd,
            'quantity' => $quantity,
            'line_total_vnd' => $subtotal,
        ]);

        return $order;
    }

    public function test_guest_cannot_access_order_list(): void
    {
        $response = $this->getJson('/api/v1/shop/me/orders');
        $response->assertStatus(401);
    }

    public function test_user_only_sees_their_own_orders_paginated(): void
    {
        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);

        // User A has 3 orders
        $orderA1 = $this->createOrderForUser($userA);
        $orderA2 = $this->createOrderForUser($userA);
        $orderA3 = $this->createOrderForUser($userA);

        // User B has 1 order
        $orderB = $this->createOrderForUser($userB);

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/v1/shop/me/orders?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('meta.total', 3);

        $orderNumbers = collect($response->json('data'))->pluck('order_number')->all();
        $this->assertContains($orderA1->order_number, $orderNumbers);
        $this->assertContains($orderA2->order_number, $orderNumbers);
        $this->assertContains($orderA3->order_number, $orderNumbers);
        $this->assertNotContains($orderB->order_number, $orderNumbers);
    }

    public function test_order_list_supports_status_filtering(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->createOrderForUser($user, OrderStatus::Pending);
        $this->createOrderForUser($user, OrderStatus::Confirmed);
        $this->createOrderForUser($user, OrderStatus::Cancelled);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/shop/me/orders?status=confirmed');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertSame(OrderStatus::Confirmed->value, $response->json('data.0.order_status'));
    }

    public function test_user_cannot_view_another_users_order_detail(): void
    {
        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);

        $orderA = $this->createOrderForUser($userA);

        $response = $this->actingAs($userB, 'sanctum')->getJson("/api/v1/shop/me/orders/{$orderA->public_id}");
        $response->assertStatus(404);
    }

    public function test_order_detail_includes_timeline_and_hides_internal_notes(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->createOrderForUser($user);
        $order->update(['internal_notes' => 'Ghi chú mật của admin']);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/shop/me/orders/{$order->public_id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'public_id',
                'order_number',
                'status_timeline',
                'can_cancel',
            ],
        ]);
        $response->assertJsonMissing(['internal_notes']);
        $this->assertTrue($response->json('data.can_cancel'));
    }

    public function test_customer_can_cancel_eligible_pending_cod_order_and_stock_is_restored(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'user']);
        $product = $this->createTrackedProduct('Tràng Hạt Bồ Đề 108 Hạt', 250000, 8);

        $order = $this->createOrderForUser($user, OrderStatus::Pending, $product, 2);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/shop/me/orders/{$order->public_id}/cancel", [
            'reason' => 'Đổi ý muốn thỉnh mẫu khác',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.order_status', OrderStatus::Cancelled->value);

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->order_status);
        $this->assertSame('Đổi ý muốn thỉnh mẫu khác', $order->cancellation_reason);
        $this->assertNotNull($order->cancelled_at);

        // Stock restored from 8 to 10 (+2)
        $product->refresh();
        $this->assertSame(10, $product->stock_quantity);

        // Audit history created
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Pending->value,
            'to_status' => OrderStatus::Cancelled->value,
            'actor_type' => 'customer',
            'actor_id' => $user->id,
        ]);

        Notification::assertSentTo($user, ShopOrderNotification::class);
    }

    public function test_customer_cannot_double_cancel_or_restore_stock_twice(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->createTrackedProduct('Chuông Đồng Cổ', 400000, 5);
        $order = $this->createOrderForUser($user, OrderStatus::Pending, $product, 1);

        // First cancel
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/shop/me/orders/{$order->public_id}/cancel");
        $product->refresh();
        $this->assertSame(6, $product->stock_quantity);

        // Second cancel attempt should be rejected
        $retryResponse = $this->actingAs($user, 'sanctum')->postJson("/api/v1/shop/me/orders/{$order->public_id}/cancel");
        $retryResponse->assertStatus(422);

        // Stock not restored again
        $product->refresh();
        $this->assertSame(6, $product->stock_quantity);
    }

    public function test_state_machine_validates_transitions_and_terminal_states(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->createOrderForUser($user, OrderStatus::Pending);

        $workflowService = app(OrderWorkflowService::class);

        // 1. Pending -> Confirmed
        $order = $workflowService->confirm($order, $admin, 'Đã gọi điện xác nhận');
        $this->assertSame(OrderStatus::Confirmed, $order->order_status);

        // 2. Confirmed -> Processing
        $order = $workflowService->startProcessing($order, $admin, 'Đang chuẩn bị gói hàng');
        $this->assertSame(OrderStatus::Processing, $order->order_status);

        // 3. Processing -> Completed (with COD collection)
        $order = $workflowService->complete($order, $admin, 'Giao hàng thành công', true);
        $this->assertSame(OrderStatus::Completed, $order->order_status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->completed_at);

        // 4. Completed is terminal -> cannot transition to Pending or Cancelled
        $this->expectException(ValidationException::class);
        $workflowService->cancel($order, $admin, 'admin', 'Hủy sau khi xong');
    }

    public function test_cannot_cancel_paid_order_without_refund_workflow(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->createOrderForUser($user, OrderStatus::Confirmed);
        $order->update(['payment_status' => PaymentStatus::Paid]);

        $workflowService = app(OrderWorkflowService::class);

        $this->expectException(ValidationException::class);
        $workflowService->cancel($order, $admin, 'admin', 'Muốn hủy đơn đã trả tiền');
    }

    public function test_order_management_does_not_create_practice_sessions_or_alter_streak(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $initialSessions = PracticeSession::count();

        $order = $this->createOrderForUser($user);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/shop/me/orders');
        $this->actingAs($user, 'sanctum')->getJson("/api/v1/shop/me/orders/{$order->public_id}");
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/shop/me/orders/{$order->public_id}/cancel");

        $this->assertSame($initialSessions, PracticeSession::count());
    }

    public function test_order_resource_allows_admin_to_delete_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->createOrderForUser($user);

        // Admin can delete
        $this->actingAs($admin);
        $this->assertTrue(\App\Filament\Resources\Orders\OrderResource::canDelete($order));

        // Normal user cannot delete
        $this->actingAs($user);
        $this->assertFalse(\App\Filament\Resources\Orders\OrderResource::canDelete($order));

        // Order soft delete works
        $order->delete();
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }
}
