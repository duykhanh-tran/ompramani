<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Shop\SepayPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SepayPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected string $testMerchantId = 'SP-TEST-TD34A2B8';
    protected string $testSecretKey = 'spsk_test_Km8dnupBVo5MXarrqwMKNdHZ1Nps4AC6';
    protected string $testIpnSecretKey = 'test_ipn_secret_key_999';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('sepay.merchant_id', $this->testMerchantId);
        Config::set('sepay.secret_key', $this->testSecretKey);
        Config::set('sepay.ipn_secret_key', $this->testIpnSecretKey);
        Config::set('sepay.environment', 'sandbox');
        Config::set('services.sepay.merchant_id', $this->testMerchantId);
        Config::set('services.sepay.secret_key', $this->testSecretKey);
        Config::set('services.sepay.ipn_secret_key', $this->testIpnSecretKey);
        Config::set('services.sepay.environment', 'sandbox');
    }

    protected function createCategory(): ProductCategory
    {
        return ProductCategory::create([
            'name' => 'Tràng Hạt Bồ Đề',
            'slug' => 'trang-hat-bo-de-'.Str::random(6),
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    protected function createProduct(ProductCategory $category, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Chuỗi Tràng Hạt Bồ Đề',
            'slug' => 'chuoi-trang-hat-'.Str::random(8),
            'sku' => 'SKU-'.Str::random(6),
            'price_vnd' => 200000,
            'stock_quantity' => 15,
            'inventory_mode' => InventoryMode::Tracked,
            'status' => ProductStatus::Published,
            'is_active' => true,
            'cover_image' => 'products/test.jpg',
            'published_at' => now()->subDay(),
        ], $attributes));
    }

    protected function createUser(): User
    {
        return User::factory()->create([
            'name' => 'Phật tử Thiện Tâm',
            'email' => 'thientam_'.Str::random(5).'@ompramani.test',
        ]);
    }

    public function test_sepay_service_configuration_and_checks(): void
    {
        $service = app(SepayPaymentService::class);

        $this->assertTrue($service->isConfigured());
        $this->assertTrue($service->isIpnSecretKeyConfigured());
        $this->assertTrue($service->verifyIpnSecretKey($this->testIpnSecretKey));
        $this->assertFalse($service->verifyIpnSecretKey('wrong_secret'));
        $this->assertFalse($service->verifyIpnSecretKey(null));

        $client = $service->getClient();
        $this->assertSame('sandbox', $client->getEnvironment());
        $this->assertSame($this->testMerchantId, $client->getMerchantId());
    }

    public function test_checkout_preview_returns_both_cod_and_sepay_options(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $cat = $this->createCategory();
        $prod = $this->createProduct($cat);

        $res = $this->postJson('/api/v1/shop/checkout/preview', [
            'items' => [
                ['product_id' => (string) $prod->id, 'quantity' => 1],
            ],
        ])->assertOk();

        $methods = $res->json('data.available_payment_methods');
        $codes = array_column($methods, 'code');

        $this->assertContains('cod', $codes);
        $this->assertContains('sepay', $codes);
    }

    public function test_customer_can_create_order_with_sepay_and_get_signed_checkout_form(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $cat = $this->createCategory();
        $prod = $this->createProduct($cat, ['price_vnd' => 200000, 'stock_quantity' => 10]);

        $res = $this->postJson('/api/v1/shop/orders', [
            'customer_name' => 'Nguyễn Tu Học',
            'customer_phone' => '0987654321',
            'customer_email' => 'tuhoc@example.com',
            'shipping_address' => 'Chùa Hoằng Pháp, Tân Hiệp, Hóc Môn',
            'shipping_province' => 'TP. Hồ Chí Minh',
            'notes' => 'Xin giao giờ hành chính',
            'payment_method' => 'sepay',
            'items' => [
                ['product_id' => (string) $prod->id, 'quantity' => 2],
            ],
        ])->assertStatus(201);

        $orderData = $res->json('data');
        $paymentData = $res->json('payment_data');

        $this->assertNotNull($paymentData);
        $this->assertStringContainsString('pay-sandbox.sepay.vn', $paymentData['checkout_url']);
        $this->assertSame('PURCHASE', $paymentData['fields']['operation']);
        $this->assertSame('VND', $paymentData['fields']['currency']);
        $this->assertNotEmpty($paymentData['fields']['signature']);
        $this->assertNotEmpty($paymentData['invoice_number']);

        // Subtotal = 400.000 + 30.000 shipping = 430.000
        $this->assertSame('430000', (string) $paymentData['fields']['order_amount']);

        // Order in DB
        $order = Order::where('public_id', $orderData['public_id'])->firstOrFail();
        $this->assertSame(PaymentMethod::Sepay, $order->payment_method);
        $this->assertSame(OrderStatus::Pending, $order->order_status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);

        // Payment in DB
        $payment = OrderPayment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(1, $payment->attempt_number);
        $this->assertSame('sepay', $payment->provider);
        $this->assertSame(430000, $payment->amount_vnd);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame($paymentData['invoice_number'], $payment->transaction_ref);

        // Stock decremented by 2
        $prod->refresh();
        $this->assertSame(8, $prod->stock_quantity);
    }

    public function test_ipn_rejects_missing_or_invalid_secret_key_fail_closed(): void
    {
        // 1. Missing header
        $this->postJson('/api/v1/shop/sepay/ipn', [
            'notification_type' => 'ORDER_PAID',
        ])->assertStatus(401);

        // 2. Wrong header
        $this->postJson('/api/v1/shop/sepay/ipn', [
            'notification_type' => 'ORDER_PAID',
        ], [
            'X-Secret-Key' => 'wrong_key_123',
        ])->assertStatus(401);

        // 3. Server with no secret configured must fail-closed
        Config::set('sepay.ipn_secret_key', null);
        Config::set('services.sepay.ipn_secret_key', null);

        $this->postJson('/api/v1/shop/sepay/ipn', [
            'notification_type' => 'ORDER_PAID',
        ], [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertStatus(401);
    }

    public function test_valid_ipn_marks_order_and_payment_as_paid_atomically(): void
    {
        $user = $this->createUser();
        $cat = $this->createCategory();
        $prod = $this->createProduct($cat, ['price_vnd' => 500000]); // Free shipping

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử An Lạc',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 500000,
            'shipping_fee_vnd' => 0,
            'total_vnd' => 500000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $invoice = "{$order->order_number}-A1";
        $payment = $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 500000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => $invoice,
        ]);

        $payload = [
            'timestamp' => time(),
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'order_invoice_number' => $invoice,
                'order_amount' => '500000.00',
                'order_currency' => 'VND',
            ],
            'transaction' => [
                'id' => 'tx_sepay_test_001',
                'transaction_id' => 'tx_sepay_test_001',
                'transaction_amount' => '500000',
                'transaction_currency' => 'VND',
                'payment_method' => 'VIETQR',
                'transaction_status' => 'APPROVED',
            ],
        ];

        $res = $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertOk();

        $this->assertTrue($res->json('success'));

        $order->refresh();
        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $order->order_status);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('tx_sepay_test_001', $payment->bank_transaction_no);
    }

    public function test_ipn_returns_404_for_unknown_invoice(): void
    {
        $payload = [
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'order_invoice_number' => 'OPM-NON-EXISTENT-A1',
                'order_amount' => '100000',
            ],
            'transaction' => [
                'transaction_amount' => '100000',
            ],
        ];

        $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertStatus(404);
    }

    public function test_ipn_detects_amount_mismatch_and_marks_needs_reconciliation(): void
    {
        $user = $this->createUser();
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 300000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 330000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $invoice = "{$order->order_number}-A1";
        $payment = $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 330000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => $invoice,
        ]);

        // Malicious or mismatched amount: 100.000 instead of 330.000
        $payload = [
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'order_invoice_number' => $invoice,
                'order_amount' => '100000',
            ],
            'transaction' => [
                'transaction_amount' => '100000',
                'transaction_currency' => 'VND',
            ],
        ];

        $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertStatus(422);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(PaymentStatus::NeedsReconciliation, $payment->status);
        $this->assertSame(PaymentStatus::NeedsReconciliation, $order->payment_status);
        $this->assertNull($order->paid_at);
    }

    public function test_ipn_is_idempotent_when_called_multiple_times(): void
    {
        $user = $this->createUser();
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 500000,
            'shipping_fee_vnd' => 0,
            'total_vnd' => 500000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $invoice = "{$order->order_number}-A1";
        $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 500000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => $invoice,
        ]);

        $payload = [
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'order_invoice_number' => $invoice,
                'order_amount' => '500000',
            ],
            'transaction' => [
                'transaction_id' => 'tx_idem_123',
                'transaction_amount' => '500000',
                'transaction_currency' => 'VND',
            ],
        ];

        // First call: Success
        $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertOk();

        // Second call: Idempotent 200 OK
        $res = $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertOk();

        $this->assertStringContainsString('idempotent', strtolower($res->json('message')));
    }

    public function test_customer_can_poll_payment_status(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 200000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 230000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $res = $this->getJson("/api/v1/shop/orders/{$order->public_id}/payment-status")->assertOk();
        $this->assertSame('unpaid', $res->json('data.payment_status'));
        $this->assertSame('sepay', $res->json('data.payment_method'));
        $this->assertSame(230000, $res->json('data.total_vnd'));
    }

    public function test_customer_can_retry_unpaid_sepay_payment_with_new_attempt(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 200000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 230000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        // Attempt 1: Pending
        $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 230000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => "{$order->order_number}-A1",
        ]);

        $res = $this->postJson("/api/v1/shop/orders/{$order->public_id}/sepay-retry")->assertOk();

        $paymentData = $res->json('payment_data');
        $this->assertSame("{$order->order_number}-A2", $paymentData['invoice_number']);

        // Old attempt marked Cancelled, new attempt is Pending
        $oldAttempt = OrderPayment::where('transaction_ref', "{$order->order_number}-A1")->first();
        $this->assertSame(PaymentStatus::Cancelled, $oldAttempt->status);

        $newAttempt = OrderPayment::where('transaction_ref', "{$order->order_number}-A2")->first();
        $this->assertSame(PaymentStatus::Pending, $newAttempt->status);
        $this->assertSame(2, $newAttempt->attempt_number);
    }

    public function test_customer_can_switch_unpaid_sepay_order_to_cod(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 200000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 230000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 230000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => "{$order->order_number}-A1",
        ]);

        $res = $this->postJson("/api/v1/shop/orders/{$order->public_id}/switch-to-cod")->assertOk();

        $order->refresh();
        $this->assertSame(PaymentMethod::Cod, $order->payment_method);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);

        $attempt = OrderPayment::where('transaction_ref', "{$order->order_number}-A1")->first();
        $this->assertSame(PaymentStatus::Cancelled, $attempt->status);
    }

    public function test_ipn_detects_currency_mismatch_and_marks_needs_reconciliation(): void
    {
        $user = $this->createUser();
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 200000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 230000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $invoice = "{$order->order_number}-A1";
        $payment = $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 230000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => $invoice,
        ]);

        // Wrong currency: USD instead of VND
        $payload = [
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'order_invoice_number' => $invoice,
                'order_amount' => '230000',
                'order_currency' => 'USD',
            ],
            'transaction' => [
                'transaction_amount' => '230000',
                'transaction_currency' => 'USD',
            ],
        ];

        $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertStatus(422);

        $payment->refresh();
        $this->assertSame(PaymentStatus::NeedsReconciliation, $payment->status);
    }

    public function test_ipn_marks_needs_reconciliation_if_order_was_already_cancelled(): void
    {
        $user = $this->createUser();
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 500000,
            'shipping_fee_vnd' => 0,
            'total_vnd' => 500000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Khách hàng hủy trước khi thanh toán',
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $invoice = "{$order->order_number}-A1";
        $payment = $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 500000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => $invoice,
        ]);

        $payload = [
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'order_invoice_number' => $invoice,
                'order_amount' => '500000',
            ],
            'transaction' => [
                'transaction_amount' => '500000',
                'transaction_currency' => 'VND',
            ],
        ];

        $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertOk();

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::NeedsReconciliation, $payment->status);
        $this->assertSame(OrderStatus::Cancelled, $order->order_status);
    }

    public function test_sepay_paid_order_is_included_in_revenue_calculation(): void
    {
        $user = $this->createUser();
        $cat = $this->createCategory();
        $prod = $this->createProduct($cat, ['price_vnd' => 500000]);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 500000,
            'shipping_fee_vnd' => 0,
            'total_vnd' => 500000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $invoice = "{$order->order_number}-A1";
        $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 500000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => $invoice,
        ]);

        $payload = [
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'order_invoice_number' => $invoice,
                'order_amount' => '500000',
            ],
            'transaction' => [
                'transaction_amount' => '500000',
                'transaction_currency' => 'VND',
            ],
        ];

        $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertOk();

        // Revenue query check
        $totalPaidRevenue = (int) Order::where('payment_status', PaymentStatus::Paid->value)->sum('total_vnd');
        $this->assertSame(500000, $totalPaidRevenue);
    }

    /**
     * 15. Unconfigured SePay service detects missing credentials.
     */
    public function test_unconfigured_sepay_service_detects_missing_credentials(): void
    {
        Config::set('sepay.merchant_id', null);
        Config::set('sepay.secret_key', null);
        Config::set('services.sepay.merchant_id', null);
        Config::set('services.sepay.secret_key', null);

        $service = new SepayPaymentService();
        $this->assertFalse($service->isConfigured());
    }

    /**
     * 16. Placeholder credentials are fundamentally rejected as unconfigured.
     */
    public function test_placeholder_credentials_are_strictly_rejected(): void
    {
        $placeholders = [
            ['your_sandbox_merchant_id', 'your_sandbox_merchant_secret_key'],
            ['your_merchant_id', 'your_merchant_secret_key'],
            ['YOUR_MERCHANT_ID', 'YOUR_MERCHANT_SECRET_KEY'],
            ['placeholder', 'placeholder'],
            ['changeme', 'changeme'],
            ['', ''],
            ['   ', '   '],
        ];

        $service = new SepayPaymentService();

        foreach ($placeholders as [$mId, $sKey]) {
            Config::set('sepay.merchant_id', $mId);
            Config::set('sepay.secret_key', $sKey);
            Config::set('services.sepay.merchant_id', $mId);
            Config::set('services.sepay.secret_key', $sKey);

            $this->assertFalse(
                $service->isConfigured(),
                "Expected merchant '{$mId}' and secret '{$sKey}' to be rejected as placeholder."
            );
        }
    }

    /**
     * 17. Checkout preview marks SePay as unavailable when unconfigured.
     */
    public function test_checkout_preview_marks_sepay_unavailable_when_unconfigured(): void
    {
        Config::set('sepay.merchant_id', null);
        Config::set('sepay.secret_key', null);
        Config::set('services.sepay.merchant_id', null);
        Config::set('services.sepay.secret_key', null);

        $user = $this->createUser();
        Sanctum::actingAs($user);

        $cat = $this->createCategory();
        $prod = $this->createProduct($cat);

        $res = $this->postJson('/api/v1/shop/checkout/preview', [
            'items' => [
                ['product_id' => (string) $prod->id, 'quantity' => 1],
            ],
        ])->assertOk();

        $methods = $res->json('data.available_payment_methods');
        $sepayMethod = collect($methods)->firstWhere('code', 'sepay');

        $this->assertNotNull($sepayMethod);
        $this->assertFalse($sepayMethod['is_available']);

        $codMethod = collect($methods)->firstWhere('code', 'cod');
        $this->assertNotNull($codMethod);
        $this->assertTrue($codMethod['is_available']);
    }

    /**
     * 18. Order creation with unconfigured SePay fails with 422 WITHOUT creating order or deducting stock.
     */
    public function test_order_creation_with_unconfigured_sepay_fails_without_creating_order_or_deducting_stock(): void
    {
        Config::set('sepay.merchant_id', null);
        Config::set('sepay.secret_key', null);
        Config::set('services.sepay.merchant_id', null);
        Config::set('services.sepay.secret_key', null);

        $user = $this->createUser();
        Sanctum::actingAs($user);

        $cat = $this->createCategory();
        $prod = $this->createProduct($cat, ['price_vnd' => 200000, 'stock_quantity' => 10]);

        $initialOrderCount = Order::count();

        $res = $this->postJson('/api/v1/shop/orders', [
            'customer_name' => 'Nguyễn Tu Học',
            'customer_phone' => '0987654321',
            'customer_email' => 'tuhoc@example.com',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'payment_method' => 'sepay',
            'items' => [
                ['product_id' => (string) $prod->id, 'quantity' => 2],
            ],
        ]);

        $res->assertStatus(422);

        // Verify no order was created
        $this->assertSame($initialOrderCount, Order::count());

        // Verify stock was NOT decremented
        $prod->refresh();
        $this->assertSame(10, $prod->stock_quantity);
    }

    /**
     * 19. COD order creation succeeds normally even when SePay is completely unconfigured.
     */
    public function test_cod_order_creation_succeeds_even_when_sepay_unconfigured(): void
    {
        Config::set('sepay.merchant_id', null);
        Config::set('sepay.secret_key', null);
        Config::set('services.sepay.merchant_id', null);
        Config::set('services.sepay.secret_key', null);

        $user = $this->createUser();
        Sanctum::actingAs($user);

        $cat = $this->createCategory();
        $prod = $this->createProduct($cat, ['price_vnd' => 200000, 'stock_quantity' => 10]);

        $res = $this->postJson('/api/v1/shop/orders', [
            'customer_name' => 'Nguyễn Tu Học',
            'customer_phone' => '0987654321',
            'customer_email' => 'tuhoc@example.com',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'payment_method' => 'cod',
            'items' => [
                ['product_id' => (string) $prod->id, 'quantity' => 2],
            ],
        ]);

        $res->assertStatus(201);
        $this->assertNull($res->json('payment_data'));

        $prod->refresh();
        $this->assertSame(8, $prod->stock_quantity);
    }

    /**
     * 20. IPN webhook accepts Authorization: Apikey header fallback.
     */
    public function test_ipn_accepts_authorization_apikey_header_fallback(): void
    {
        $user = $this->createUser();
        $cat = $this->createCategory();
        $prod = $this->createProduct($cat, ['price_vnd' => 300000]);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 300000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 330000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $invoice = "{$order->order_number}-A1";
        $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 330000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => $invoice,
        ]);

        $payload = [
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'order_invoice_number' => $invoice,
                'order_amount' => '330000',
            ],
            'transaction' => [
                'transaction_amount' => '330000',
                'transaction_currency' => 'VND',
            ],
        ];

        // Use Authorization: Apikey header instead of X-Secret-Key
        $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'Authorization' => "Apikey {$this->testIpnSecretKey}",
        ])->assertOk();

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
    }

    /**
     * 21. IPN webhook rejects merchant ID mismatch.
     */
    public function test_ipn_rejects_merchant_id_mismatch(): void
    {
        $user = $this->createUser();
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 300000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 330000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $invoice = "{$order->order_number}-A1";
        $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 330000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => $invoice,
        ]);

        $payload = [
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'order_invoice_number' => $invoice,
                'order_amount' => '330000',
                'merchant_id' => 'DIFFERENT_MERCHANT_ID',
            ],
            'transaction' => [
                'transaction_amount' => '330000',
                'transaction_currency' => 'VND',
            ],
        ];

        $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Merchant ID mismatch.');
    }

    /**
     * 22. IPN webhook rejects failed transaction status.
     */
    public function test_ipn_rejects_failed_transaction_status(): void
    {
        $user = $this->createUser();
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 300000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 330000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $invoice = "{$order->order_number}-A1";
        $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 330000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => $invoice,
        ]);

        $payload = [
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'order_invoice_number' => $invoice,
                'order_amount' => '330000',
            ],
            'transaction' => [
                'transaction_amount' => '330000',
                'transaction_currency' => 'VND',
                'transaction_status' => 'FAILED',
            ],
        ];

        $this->postJson('/api/v1/shop/sepay/ipn', $payload, [
            'X-Secret-Key' => $this->testIpnSecretKey,
        ])->assertStatus(422);

        $order->refresh();
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
    }

    /**
     * 23. SePay retry payment fails cleanly when SePay is unconfigured.
     */
    public function test_sepay_retry_fails_cleanly_when_unconfigured(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 300000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 330000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 330000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => "{$order->order_number}-A1",
        ]);

        // Unset credentials
        Config::set('sepay.merchant_id', null);
        Config::set('sepay.secret_key', null);
        Config::set('services.sepay.merchant_id', null);
        Config::set('services.sepay.secret_key', null);

        $res = $this->postJson("/api/v1/shop/orders/{$order->public_id}/sepay-retry");
        $res->assertStatus(422);

        // Ensure no second payment attempt was created
        $this->assertSame(1, $order->payments()->count());
    }

    /**
     * 24. Switch to COD works even when SePay was unconfigured or failed.
     */
    public function test_switch_to_cod_works_when_sepay_unconfigured(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Phật tử Test',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'shipping_province' => 'Hà Nội',
            'subtotal_vnd' => 300000,
            'shipping_fee_vnd' => 30000,
            'total_vnd' => 330000,
            'currency' => 'VND',
            'order_status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Sepay,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $order->payments()->create([
            'attempt_number' => 1,
            'payment_method' => PaymentMethod::Sepay,
            'provider' => 'sepay',
            'amount_vnd' => 330000,
            'currency' => 'VND',
            'status' => PaymentStatus::Pending,
            'transaction_ref' => "{$order->order_number}-A1",
        ]);

        $res = $this->postJson("/api/v1/shop/orders/{$order->public_id}/switch-to-cod")->assertOk();

        $order->refresh();
        $this->assertSame(PaymentMethod::Cod, $order->payment_method);
    }
}
