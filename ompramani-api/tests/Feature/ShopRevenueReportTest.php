<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Pages\ShopRevenueReport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShopRevenueReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Quản Trị Viên Chùa',
            'email' => 'admin@ompramani.test',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->customer = User::create([
            'name' => 'Phật Tử Huân Tu',
            'email' => 'phattu@ompramani.test',
            'password' => bcrypt('Password123!'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->category = ProductCategory::create([
            'name' => 'Chuông - Mõ',
            'slug' => 'chuong-mo',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createProduct(string $name, int $price, int $stock = 20): Product
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

    private function createPaidOrder(User $customer, array $itemsData, ?Carbon $paidAt = null): Order
    {
        $subtotal = 0;
        foreach ($itemsData as $row) {
            $subtotal += $row['unit_price'] * $row['quantity'];
        }
        $shippingFee = $subtotal >= 500000 ? 0 : 30000;
        $total = $subtotal + $shippingFee;

        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => '0901234567',
            'customer_email' => $customer->email,
            'shipping_address' => 'Số 1 Chùa Một Cột, Ba Đình',
            'shipping_province' => 'Hà Nội',
            'shipping_fee_vnd' => $shippingFee,
            'subtotal_vnd' => $subtotal,
            'total_vnd' => $total,
            'currency' => 'VND',
            'order_status' => OrderStatus::Completed,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Paid,
            'placed_at' => $paidAt ?? now(),
            'paid_at' => $paidAt ?? now(),
            'completed_at' => $paidAt ?? now(),
        ]);

        foreach ($itemsData as $row) {
            /** @var Product $prod */
            $prod = $row['product'];
            $qty = $row['quantity'];
            $unitPrice = $row['unit_price'];

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $prod->id,
                'product_public_id' => $prod->public_id,
                'product_name_snapshot' => $prod->name,
                'product_sku_snapshot' => $prod->sku,
                'product_image_snapshot' => $prod->cover_image,
                'unit_price_vnd' => $unitPrice,
                'quantity' => $qty,
                'line_total_vnd' => $unitPrice * $qty,
            ]);
        }

        return $order;
    }

    public function test_guest_cannot_access_revenue_report_page(): void
    {
        $this->get('/admin/thong-ke-doanh-thu-ban-chay')
            ->assertRedirect('/admin/login');
    }

    public function test_non_admin_cannot_access_revenue_report_page(): void
    {
        $this->actingAs($this->customer)
            ->get('/admin/thong-ke-doanh-thu-ban-chay')
            ->assertForbidden();
    }

    public function test_admin_can_access_revenue_report_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/thong-ke-doanh-thu-ban-chay')
            ->assertOk();
    }

    public function test_unpaid_or_pending_orders_are_not_counted_in_revenue(): void
    {
        $prod = $this->createProduct('Bộ Chuông Mõ Tụng Kinh', 1400000);

        // 1. Pending unpaid order
        Order::create([
            'user_id' => $this->customer->id,
            'customer_name' => 'Khách A',
            'customer_phone' => '0901111111',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'shipping_fee_vnd' => 0,
            'subtotal_vnd' => 1400000,
            'total_vnd' => 1400000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Unpaid,
            'placed_at' => now(),
            'paid_at' => null,
        ]);

        // 2. Cancelled order
        Order::create([
            'user_id' => $this->customer->id,
            'customer_name' => 'Khách B',
            'customer_phone' => '0902222222',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'shipping_fee_vnd' => 0,
            'subtotal_vnd' => 1400000,
            'total_vnd' => 1400000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Cancelled,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Cancelled,
            'placed_at' => now(),
            'paid_at' => null,
        ]);

        $component = new ShopRevenueReport;
        $component->timePreset = '30days';
        $metrics = $component->getRevenueMetrics();

        $this->assertSame(0, $metrics['collected_revenue']);
        $this->assertSame(0, $metrics['paid_orders_count']);
        $this->assertSame(0, $metrics['net_revenue']);
    }

    public function test_verified_paid_orders_are_counted_in_revenue(): void
    {
        $p1 = $this->createProduct('Chuông Gia Trì Huế', 800000);
        $p2 = $this->createProduct('Mõ Gỗ Mít Điêu Khắc', 600000);

        // Order 1: 800.000 + 0 ship = 800.000 VND
        $this->createPaidOrder($this->customer, [
            ['product' => $p1, 'quantity' => 1, 'unit_price' => 800000],
        ], now()->subDays(2));

        // Order 2: 600.000 + 0 ship = 600.000 VND
        $this->createPaidOrder($this->customer, [
            ['product' => $p2, 'quantity' => 1, 'unit_price' => 600000],
        ], now()->subDay());

        $component = new ShopRevenueReport;
        $component->timePreset = '30days';
        $metrics = $component->getRevenueMetrics();

        $this->assertSame(1400000, $metrics['collected_revenue']);
        $this->assertSame(2, $metrics['paid_orders_count']);
        $this->assertSame(1400000, $metrics['net_revenue']);
        $this->assertSame(0, $metrics['refunded_amount']);
    }

    public function test_completed_refund_is_deducted_from_net_revenue(): void
    {
        $p = $this->createProduct('Tràng Hạt Gỗ Bồ Đề 108 Hạt', 500000);

        $order = $this->createPaidOrder($this->customer, [
            ['product' => $p, 'quantity' => 1, 'unit_price' => 500000],
        ], now()->subDays(3));

        // Create a COMPLETED refund of 200.000 VND
        OrderRefund::create([
            'order_id' => $order->id,
            'amount_vnd' => 200000,
            'status' => 'completed',
            'reason' => 'Đổi 1 hạt bồ đề có vết nứt',
            'actor_id' => $this->admin->id,
            'refunded_at' => now()->subDay(),
        ]);

        // Create a PENDING refund (must NOT be deducted!)
        OrderRefund::create([
            'order_id' => $order->id,
            'amount_vnd' => 100000,
            'status' => 'pending',
            'reason' => 'Yêu cầu chờ duyệt',
            'actor_id' => $this->admin->id,
            'refunded_at' => null,
        ]);

        $component = new ShopRevenueReport;
        $component->timePreset = '30days';
        $metrics = $component->getRevenueMetrics();

        $this->assertSame(500000, $metrics['collected_revenue']);
        $this->assertSame(200000, $metrics['refunded_amount']);
        $this->assertSame(300000, $metrics['net_revenue']); // 500.000 - 200.000
    }

    public function test_date_range_filter_excludes_orders_outside_period(): void
    {
        $p = $this->createProduct('Khánh Đồng Đúc', 300000);

        // Order A: paid 40 days ago (outside 30 days)
        $this->createPaidOrder($this->customer, [
            ['product' => $p, 'quantity' => 1, 'unit_price' => 300000],
        ], now()->subDays(40));

        // Order B: paid 5 days ago (inside 30 days)
        $this->createPaidOrder($this->customer, [
            ['product' => $p, 'quantity' => 1, 'unit_price' => 300000],
        ], now()->subDays(5));

        $component = new ShopRevenueReport;
        $component->timePreset = '30days';
        $metrics = $component->getRevenueMetrics();

        // 300.000 + 30.000 ship = 330.000 VND
        $this->assertSame(330000, $metrics['collected_revenue']);
        $this->assertSame(1, $metrics['paid_orders_count']);
    }

    public function test_revenue_uses_order_item_snapshot_price_not_current_product_price(): void
    {
        $prod = $this->createProduct('Bộ Kinh Pháp Hoa', 200000);

        // Order sold at snapshot price 200.000 VND
        $this->createPaidOrder($this->customer, [
            ['product' => $prod, 'quantity' => 2, 'unit_price' => 200000],
        ], now()->subDay());

        // Later admin changes product price to 500.000 VND
        $prod->price_vnd = 500000;
        $prod->save();

        $component = new ShopRevenueReport;
        $component->timePreset = '30days';
        $bestSellers = $component->getBestSellers();

        $this->assertCount(1, $bestSellers);
        $this->assertSame(2, (int) $bestSellers->first()->total_quantity_sold);
        // Revenue must be 2 * 200.000 = 400.000 VND, NOT 2 * 500.000 VND!
        $this->assertSame(400000, (int) $bestSellers->first()->total_revenue_vnd);
    }

    public function test_best_sellers_ranked_by_quantity_sold_and_limited_to_top_10(): void
    {
        // Create 12 products
        $products = [];
        for ($i = 1; $i <= 12; $i++) {
            $products[$i] = $this->createProduct("Vật Phẩm {$i}", 100000 * $i, 100);
        }

        // Sell with different quantities
        // Product 5 sells 20 units
        $this->createPaidOrder($this->customer, [
            ['product' => $products[5], 'quantity' => 20, 'unit_price' => 500000],
        ], now()->subDays(2));

        // Product 3 sells 15 units
        $this->createPaidOrder($this->customer, [
            ['product' => $products[3], 'quantity' => 15, 'unit_price' => 300000],
        ], now()->subDays(2));

        // Products 1..10 each sell 1..10 units
        for ($i = 1; $i <= 10; $i++) {
            if ($i !== 5 && $i !== 3) {
                $this->createPaidOrder($this->customer, [
                    ['product' => $products[$i], 'quantity' => $i, 'unit_price' => 100000 * $i],
                ], now()->subDays(2));
            }
        }

        $component = new ShopRevenueReport;
        $component->timePreset = '30days';
        $bestSellers = $component->getBestSellers();

        // Must limit to top 10
        $this->assertCount(10, $bestSellers);

        // #1 must be Product 5 with 20 quantity
        $this->assertSame($products[5]->name, $bestSellers[0]->product_name_snapshot);
        $this->assertSame(20, (int) $bestSellers[0]->total_quantity_sold);

        // #2 must be Product 3 with 15 quantity
        $this->assertSame($products[3]->name, $bestSellers[1]->product_name_snapshot);
        $this->assertSame(15, (int) $bestSellers[1]->total_quantity_sold);
    }

    public function test_empty_state_when_no_sales(): void
    {
        $component = new ShopRevenueReport;
        $component->timePreset = '30days';

        $this->assertTrue($component->getBestSellers()->isEmpty());
        $metrics = $component->getRevenueMetrics();
        $this->assertSame(0, $metrics['collected_revenue']);
        $this->assertSame(0, $metrics['paid_orders_count']);

        $chartData = $component->getDailyRevenueData();
        $this->assertSame(0, $chartData['total_in_chart']);
        $this->assertTrue($component->getTop5BestSellers()->isEmpty());
    }

    public function test_page_renders_only_two_kpi_cards_and_no_refund_card(): void
    {
        $p = $this->createProduct('Tượng Phật A Di Đà', 2500000);
        $order = $this->createPaidOrder($this->customer, [
            ['product' => $p, 'quantity' => 1, 'unit_price' => 2500000],
        ], now());

        OrderRefund::create([
            'order_id' => $order->id,
            'amount_vnd' => 500000,
            'status' => 'completed',
            'reason' => 'Đổi phiên bản',
            'actor_id' => $this->admin->id,
            'refunded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/thong-ke-doanh-thu-ban-chay');

        $response->assertOk();

        // Must display the 2 KPI titles
        $response->assertSee('Doanh thu thực thu');
        $response->assertSee('Số đơn đã thanh toán');

        // Must NOT display "Tiền đã hoàn" KPI card title
        $response->assertDontSee('Tiền đã hoàn');
        $response->assertDontSee('Tiền Đã Hoàn');
        $response->assertDontSee('Doanh thu đã thu');

        // Must display the 2 charts titles
        $response->assertSee('Doanh Thu Theo Thời Gian');
        $response->assertSee('Sản Phẩm Bán Chạy (Top 5)');

        // Must display the Top 10 table
        $response->assertSee('Chi Tiết Sản Phẩm Bán Chạy (Top 10)');
    }

    public function test_top_5_best_sellers_limits_to_five_and_calculates_bar_percentage(): void
    {
        $products = [];
        for ($i = 1; $i <= 8; $i++) {
            $products[$i] = $this->createProduct("Sản phẩm {$i}", 100000 * $i, 100);
            $this->createPaidOrder($this->customer, [
                ['product' => $products[$i], 'quantity' => $i * 5, 'unit_price' => 100000 * $i],
            ], now()->subDays(2));
        }

        $component = new ShopRevenueReport;
        $component->timePreset = '30days';

        $top5 = $component->getTop5BestSellers();

        // Limit strictly to 5
        $this->assertCount(5, $top5);

        // First item is product 8 (qty 40), percentage must be 100%
        $this->assertSame($products[8]->name, $top5[0]->product_name_snapshot);
        $this->assertSame(40, (int) $top5[0]->total_quantity_sold);
        $this->assertSame(100, $top5[0]->bar_percentage);

        // Fifth item is product 4 (qty 20), percentage is 20/40 * 100 = 50%
        $this->assertSame($products[4]->name, $top5[4]->product_name_snapshot);
        $this->assertSame(20, (int) $top5[4]->total_quantity_sold);
        $this->assertSame(50, $top5[4]->bar_percentage);
    }

    public function test_daily_revenue_data_properly_deducts_refunds_per_day(): void
    {
        $p = $this->createProduct('Chuông Xoay Tây Tạng', 1200000);

        $date1 = now()->subDays(3);
        $order1 = $this->createPaidOrder($this->customer, [
            ['product' => $p, 'quantity' => 1, 'unit_price' => 1200000],
        ], $date1);

        // Completed refund on the same date for 400.000 VND
        OrderRefund::create([
            'order_id' => $order1->id,
            'amount_vnd' => 400000,
            'status' => 'completed',
            'reason' => 'Đổi phụ kiện',
            'actor_id' => $this->admin->id,
            'refunded_at' => $date1,
        ]);

        $component = new ShopRevenueReport;
        $component->timePreset = '7days';

        $dailyData = $component->getDailyRevenueData();
        $tz = 'Asia/Ho_Chi_Minh';
        $dayKey = $date1->timezone($tz)->format('d/m');

        $targetDay = collect($dailyData['series'])->firstWhere('date', $dayKey);
        $this->assertNotNull($targetDay);

        // Net daily revenue must be 1.200.000 - 400.000 = 800.000 VND
        $this->assertSame(800000, $targetDay['revenue']);
        $this->assertSame(1, $targetDay['orders_count']);
    }

    public function test_chart_widgets_empty_state_when_no_sales(): void
    {
        $trendChart = new \App\Filament\Widgets\ShopRevenueTrendChart;
        $trendChart->timePreset = '30days';

        $this->assertTrue($trendChart->isEmpty());
        $this->assertSame('Chưa có dữ liệu doanh thu trong khoảng thời gian này.', $trendChart->getEmptyStateHeading());

        $bestSellersChart = new \App\Filament\Widgets\ShopBestSellersChart;
        $bestSellersChart->timePreset = '30days';

        $this->assertTrue($bestSellersChart->isEmpty());
        $this->assertSame('Chưa có sản phẩm phát sinh giao dịch hợp lệ.', $bestSellersChart->getEmptyStateHeading());
    }

    public function test_chart_widgets_render_data_when_sales_exist(): void
    {
        $p = $this->createProduct('Mõ Gỗ Mít Tụng Kinh', 850000);
        $this->createPaidOrder($this->customer, [
            ['product' => $p, 'quantity' => 3, 'unit_price' => 850000],
        ], now()->subDays(2));

        $trendChart = new \App\Filament\Widgets\ShopRevenueTrendChart;
        $trendChart->timePreset = '7days';

        $this->assertFalse($trendChart->isEmpty());

        $bestSellersChart = new \App\Filament\Widgets\ShopBestSellersChart;
        $bestSellersChart->timePreset = '7days';

        $this->assertFalse($bestSellersChart->isEmpty());
    }
}
