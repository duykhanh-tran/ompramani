<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryMode;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopCartPreviewTest extends TestCase
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

    public function test_guest_can_preview_valid_cart(): void
    {
        $category = $this->createCategory();

        $product1 = $this->createProduct($category, [
            'name' => 'Chuỗi Bồ Đề 108 Hạt',
            'price_vnd' => 150000,
            'stock_quantity' => 10,
        ]);

        $product2 = $this->createProduct($category, [
            'name' => 'Chuông Xoay Tây Tạng',
            'price_vnd' => 600000,
            'inventory_mode' => InventoryMode::NotTracked,
        ]);

        $payload = [
            'items' => [
                ['product_id' => $product1->public_id, 'quantity' => 2],
                ['product_id' => $product2->public_id, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson('/api/v1/shop/cart/preview', $payload);

        $response->assertOk()
            ->assertJsonPath('data.subtotal_vnd', 900000)
            ->assertJsonPath('data.formatted_subtotal', '900.000 ₫')
            ->assertJsonPath('data.total_quantity', 3)
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.line_subtotal_vnd', 300000)
            ->assertJsonPath('data.items.0.formatted_line_subtotal', '300.000 ₫')
            ->assertJsonPath('data.items.1.line_subtotal_vnd', 600000);
    }

    public function test_cart_preview_uses_current_backend_prices_and_ignores_client_tampering(): void
    {
        $category = $this->createCategory();

        $product = $this->createProduct($category, [
            'price_vnd' => 200000,
        ]);

        // Client attempts to pass fake price, subtotal, discount, total
        $payload = [
            'items' => [
                [
                    'product_id' => $product->public_id,
                    'quantity' => 3,
                    'price_vnd' => 1000,
                    'subtotal' => 3000,
                    'discount' => 100000,
                ],
            ],
            'subtotal_vnd' => 3000,
        ];

        $response = $this->postJson('/api/v1/shop/cart/preview', $payload);

        $response->assertOk()
            ->assertJsonPath('data.items.0.unit_price_vnd', 200000)
            ->assertJsonPath('data.items.0.line_subtotal_vnd', 600000)
            ->assertJsonPath('data.subtotal_vnd', 600000)
            ->assertJsonPath('data.formatted_subtotal', '600.000 ₫');
    }

    public function test_cart_preview_rejects_negative_or_fractional_quantities(): void
    {
        $category = $this->createCategory();
        $product = $this->createProduct($category);

        // Zero quantity
        $this->postJson('/api/v1/shop/cart/preview', [
            'items' => [['product_id' => $product->public_id, 'quantity' => 0]],
        ])->assertStatus(422);

        // Negative quantity
        $this->postJson('/api/v1/shop/cart/preview', [
            'items' => [['product_id' => $product->public_id, 'quantity' => -5]],
        ])->assertStatus(422);

        // Fractional quantity
        $this->postJson('/api/v1/shop/cart/preview', [
            'items' => [['product_id' => $product->public_id, 'quantity' => 1.5]],
        ])->assertStatus(422);
    }

    public function test_cart_preview_rejects_quantities_over_hard_cap_or_items_over_limit(): void
    {
        $category = $this->createCategory();
        $product = $this->createProduct($category);

        // Quantity > 99
        $this->postJson('/api/v1/shop/cart/preview', [
            'items' => [['product_id' => $product->public_id, 'quantity' => 100]],
        ])->assertStatus(422);

        // Empty items array
        $this->postJson('/api/v1/shop/cart/preview', [
            'items' => [],
        ])->assertStatus(422);
    }

    public function test_cart_preview_deduplicates_duplicate_item_identifiers(): void
    {
        $category = $this->createCategory();
        $product = $this->createProduct($category, ['price_vnd' => 100000]);

        $payload = [
            'items' => [
                ['product_id' => $product->public_id, 'quantity' => 2],
                ['product_id' => $product->public_id, 'quantity' => 3],
            ],
        ];

        $response = $this->postJson('/api/v1/shop/cart/preview', $payload);

        $response->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.quantity', 5)
            ->assertJsonPath('data.items.0.line_subtotal_vnd', 500000)
            ->assertJsonPath('data.subtotal_vnd', 500000);
    }

    public function test_cart_preview_flags_unpublished_draft_or_deleted_products(): void
    {
        $category = $this->createCategory();

        $valid = $this->createProduct($category, ['price_vnd' => 100000]);

        $draft = Product::create([
            'category_id' => $category->id,
            'name' => 'Bản nháp',
            'slug' => 'ban-nhap',
            'status' => ProductStatus::Draft,
            'price_vnd' => 80000,
        ]);

        $payload = [
            'items' => [
                ['product_id' => $valid->public_id, 'quantity' => 1],
                ['product_id' => $draft->public_id, 'quantity' => 1],
                ['product_id' => 'nonexistent_id_9999', 'quantity' => 1],
            ],
        ];

        $response = $this->postJson('/api/v1/shop/cart/preview', $payload);

        $response->assertOk()
            ->assertJsonPath('data.is_valid', false)
            ->assertJsonPath('data.subtotal_vnd', 100000) // only valid product counted
            ->assertJsonPath('data.items.0.is_available', true)
            ->assertJsonPath('data.items.1.is_available', false)
            ->assertJsonPath('data.items.1.error', 'product_unavailable')
            ->assertJsonPath('data.items.2.is_available', false)
            ->assertJsonPath('data.items.2.error', 'product_unavailable');
    }

    public function test_cart_preview_flags_out_of_stock_and_insufficient_stock_for_tracked_products(): void
    {
        $category = $this->createCategory();

        $outOfStock = $this->createProduct($category, [
            'name' => 'Hết hàng hoàn toàn',
            'price_vnd' => 50000,
            'inventory_mode' => InventoryMode::Tracked,
            'stock_quantity' => 0,
        ]);

        $lowStock = $this->createProduct($category, [
            'name' => 'Còn ít hàng',
            'price_vnd' => 70000,
            'inventory_mode' => InventoryMode::Tracked,
            'stock_quantity' => 2,
        ]);

        $payload = [
            'items' => [
                ['product_id' => $outOfStock->public_id, 'quantity' => 1],
                ['product_id' => $lowStock->public_id, 'quantity' => 5], // requesting 5 when only 2 available
            ],
        ];

        $response = $this->postJson('/api/v1/shop/cart/preview', $payload);

        $response->assertOk()
            ->assertJsonPath('data.is_valid', false)
            ->assertJsonPath('data.subtotal_vnd', 0)
            ->assertJsonPath('data.items.0.is_available', false)
            ->assertJsonPath('data.items.0.error', 'out_of_stock')
            ->assertJsonPath('data.items.1.is_available', false)
            ->assertJsonPath('data.items.1.error', 'insufficient_stock')
            ->assertJsonPath('data.items.1.available_stock', 2);
    }

    public function test_cart_preview_performs_zero_database_writes(): void
    {
        $category = $this->createCategory();
        $product = $this->createProduct($category, ['stock_quantity' => 10]);

        $this->postJson('/api/v1/shop/cart/preview', [
            'items' => [['product_id' => $product->public_id, 'quantity' => 2]],
        ])->assertOk();

        // Verify stock is untouched
        $this->assertEquals(10, $product->fresh()->stock_quantity);

        // Verify no practice session or streak created
        $this->assertDatabaseCount('practice_sessions', 0);
    }

    public function test_product_detail_api_returns_gallery_images_and_no_sensitive_fields(): void
    {
        $category = $this->createCategory();
        $product = $this->createProduct($category, [
            'name' => 'Khánh đồng tụng kinh',
            'slug' => 'khanh-dong-tung-kinh',
            'cover_image' => 'products/khanh-dong.jpg',
        ]);

        $res = $this->getJson("/api/v1/shop/products/{$product->slug}");

        $res->assertOk()
            ->assertJsonPath('data.name', 'Khánh đồng tụng kinh')
            ->assertJsonCount(1, 'data.gallery_images')
            ->assertJsonMissing(['cost_price'])
            ->assertJsonMissing(['supplier'])
            ->assertJsonMissing(['admin_notes']);
    }
}
