<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Shop\InventoryService;
use App\Services\Shop\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ShopInventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private ProductCategory $category;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Quản Trị Viên Kho',
            'email' => 'admin-kho@ompramani.test',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->customer = User::create([
            'name' => 'Phật Tử Mua Hàng',
            'email' => 'customer-kho@ompramani.test',
            'password' => bcrypt('Password123!'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->category = ProductCategory::create([
            'name' => 'Pháp Khí Đồng',
            'slug' => 'phap-khi-dong',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->inventoryService = new InventoryService;
    }

    private function createTrackedProduct(string $name, int $stock = 10, int $price = 500000): Product
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

    private function createNotTrackedProduct(string $name): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => str()->slug($name).'-'.strtolower(str()->random(6)),
            'sku' => 'SKU-'.strtoupper(str()->random(6)),
            'price_vnd' => 100000,
            'inventory_mode' => InventoryMode::NotTracked,
            'stock_quantity' => 0,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_guest_cannot_access_inventory_page(): void
    {
        $this->get('/admin/quan-ly-kho')
            ->assertRedirect('/admin/login');
    }

    public function test_non_admin_cannot_access_inventory_page(): void
    {
        $this->actingAs($this->customer)
            ->get('/admin/quan-ly-kho')
            ->assertForbidden();
    }

    public function test_admin_can_access_inventory_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/quan-ly-kho')
            ->assertOk();
    }

    public function test_increment_stock_updates_quantity_and_records_movement(): void
    {
        $product = $this->createTrackedProduct('Chuông Xoay 15cm', 10);

        $movement = $this->inventoryService->incrementStock(
            $product,
            15,
            $this->admin,
            'Nhập bổ sung 15 chiếc từ xưởng đúc đồng'
        );

        $product->refresh();
        $this->assertSame(25, $product->stock_quantity);

        $this->assertSame(10, $movement->quantity_before);
        $this->assertSame(25, $movement->quantity_after);
        $this->assertSame(15, $movement->change);
        $this->assertSame('restock', $movement->type);
        $this->assertSame('Nhập bổ sung 15 chiếc từ xưởng đúc đồng', $movement->reason);
        $this->assertSame($this->admin->id, $movement->actor_id);
    }

    public function test_decrement_stock_updates_quantity_and_records_movement(): void
    {
        $product = $this->createTrackedProduct('Mõ Gỗ Mít 20cm', 20);

        $movement = $this->inventoryService->decrementStock(
            $product,
            6,
            $this->admin,
            'Xuất 6 chiếc bị trầy sơn gửi trả nhà xưởng'
        );

        $product->refresh();
        $this->assertSame(14, $product->stock_quantity);

        $this->assertSame(20, $movement->quantity_before);
        $this->assertSame(14, $movement->quantity_after);
        $this->assertSame(-6, $movement->change);
        $this->assertSame('adjustment', $movement->type);
    }

    public function test_adjust_stock_sets_exact_quantity_and_records_movement(): void
    {
        $product = $this->createTrackedProduct('Tràng Hạt Trầm Hương', 18);

        $movement = $this->inventoryService->adjustStock(
            $product,
            22,
            $this->admin,
            'Kiểm kê thực tế cuối tháng 11',
            'stocktake'
        );

        $product->refresh();
        $this->assertSame(22, $product->stock_quantity);

        $this->assertSame(18, $movement->quantity_before);
        $this->assertSame(22, $movement->quantity_after);
        $this->assertSame(4, $movement->change);
        $this->assertSame('stocktake', $movement->type);
    }

    public function test_negative_stock_adjustment_is_rejected(): void
    {
        $product = $this->createTrackedProduct('Bộ Chuông Mõ Tụng Kinh', 5);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Số lượng tồn kho không thể là số âm.');

        $this->inventoryService->adjustStock($product, -1, $this->admin, 'Lỗi âm kho');
    }

    public function test_decrement_beyond_current_stock_is_rejected(): void
    {
        $product = $this->createTrackedProduct('Khánh Đồng Nhỏ', 3);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tồn kho hiện tại chỉ còn 3');

        $this->inventoryService->decrementStock($product, 5, $this->admin, 'Xuất vượt tồn');
    }

    public function test_adjusting_not_tracked_product_is_rejected(): void
    {
        $product = $this->createNotTrackedProduct('Kinh Bản Điện Tử');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('chế độ không theo dõi tồn kho');

        $this->inventoryService->adjustStock($product, 10, $this->admin, 'Thử chỉnh kho');
    }

    public function test_inventory_adjustment_does_not_alter_price_or_other_attributes(): void
    {
        $product = $this->createTrackedProduct('Khánh Đồng Cỡ Lớn', 10, 1500000);
        $originalPrice = $product->price_vnd;
        $originalSlug = $product->slug;

        $this->inventoryService->incrementStock($product, 5, $this->admin, 'Nhập thêm');

        $product->refresh();
        $this->assertSame($originalPrice, $product->price_vnd);
        $this->assertSame($originalSlug, $product->slug);
    }

    public function test_order_cancellation_records_inventory_movement(): void
    {
        $product = $this->createTrackedProduct('Chuông Đồng Đài Loan', 10);

        $order = Order::create([
            'user_id' => $this->customer->id,
            'customer_name' => 'Khách Hủy',
            'customer_phone' => '0909999999',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'shipping_fee_vnd' => 0,
            'subtotal_vnd' => 1000000,
            'total_vnd' => 1000000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Unpaid,
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_public_id' => $product->public_id,
            'product_name_snapshot' => $product->name,
            'product_sku_snapshot' => $product->sku,
            'unit_price_vnd' => 500000,
            'quantity' => 2,
            'line_total_vnd' => 1000000,
        ]);

        $workflow = new OrderWorkflowService;
        $workflow->cancel($order, $this->admin, 'admin', 'customer_requested', 'Khách đổi ý');

        $product->refresh();
        $this->assertSame(12, $product->stock_quantity); // 10 + 2

        $movement = InventoryMovement::where('product_id', $product->id)
            ->where('type', 'order_cancelled')
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame(10, $movement->quantity_before);
        $this->assertSame(12, $movement->quantity_after);
        $this->assertSame(2, $movement->change);
        $this->assertStringContainsString($order->order_number, $movement->reason);
    }

    public function test_product_inventory_movements_relationship(): void
    {
        $product = $this->createTrackedProduct('Tượng Phật A Di Đà Mini', 10);

        $this->inventoryService->incrementStock($product, 5, $this->admin, 'Đợt 1');
        $this->inventoryService->decrementStock($product, 2, $this->admin, 'Đợt 2');

        $this->assertCount(2, $product->inventoryMovements);
    }
}
