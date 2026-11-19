<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryMode;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class ShopProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function createCategory(array $attributes = []): ProductCategory
    {
        return ProductCategory::create(array_merge([
            'name' => 'Tràng hạt & Pháp khí',
            'slug' => 'trang-hat-phap-khi',
            'description' => 'Các loại tràng hạt, chuỗi bồ đề, chuông mõ phục vụ tu học.',
            'sort_order' => 1,
            'is_active' => true,
        ], $attributes));
    }

    protected function createProduct(ProductCategory $category, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Chuỗi Bồ Đề 108 Hạt',
            'slug' => 'chuoi-bo-de-108-hat',
            'sku' => 'BD-'.Str::random(8),
            'short_description' => 'Chuỗi hạt bồ đề tự nhiên hỗ trợ niệm Phật, trì chú.',
            'description' => 'Chất liệu hạt bồ đề tự nhiên được gia công tỉ mỉ, mộc mạc, phù hợp hành trì hàng ngày.',
            'price_vnd' => 150000,
            'stock_quantity' => 20,
            'inventory_mode' => InventoryMode::Tracked,
            'status' => ProductStatus::Published,
            'is_active' => true,
            'cover_image' => 'products/chuoi-bo-de.jpg',
            'published_at' => now()->subDay(),
        ], $attributes));
    }

    public function test_can_get_active_categories_with_product_counts(): void
    {
        $cat1 = $this->createCategory(['name' => 'Pháp khí', 'slug' => 'phap-khi', 'sort_order' => 1]);
        $cat2 = $this->createCategory(['name' => 'Hương trầm', 'slug' => 'huong-tram', 'sort_order' => 2]);
        $inactiveCat = $this->createCategory(['name' => 'Đã ẩn', 'slug' => 'da-an', 'is_active' => false]);

        $this->createProduct($cat1, ['name' => 'Mõ gỗ nhỏ', 'slug' => 'mo-go-nho']);
        $this->createProduct($cat1, ['name' => 'Chuông đồng', 'slug' => 'chuong-dong', 'status' => ProductStatus::Draft]);

        $response = $this->getJson('/api/v1/shop/categories');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Pháp khí', 'products_count' => 1])
            ->assertJsonFragment(['name' => 'Hương trầm', 'products_count' => 0])
            ->assertJsonMissing(['name' => 'Đã ẩn']);
    }

    public function test_guest_can_list_published_products_only(): void
    {
        $category = $this->createCategory();

        $published = $this->createProduct($category, ['name' => 'Chuỗi Trầm Hương 108', 'slug' => 'chuoi-tram-huong-108']);

        $draft = Product::create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm nháp',
            'slug' => 'san-pham-nhap',
            'status' => ProductStatus::Draft,
            'is_active' => true,
        ]);

        $archived = Product::create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm đã lưu trữ',
            'slug' => 'san-pham-da-luu-tru',
            'status' => ProductStatus::Archived,
            'is_active' => true,
        ]);

        $inactive = Product::create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm tạm ẩn',
            'slug' => 'san-pham-tam-an',
            'status' => ProductStatus::Published,
            'is_active' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/shop/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chuỗi Trầm Hương 108')
            ->assertJsonMissing(['name' => 'Sản phẩm nháp'])
            ->assertJsonMissing(['name' => 'Sản phẩm đã lưu trữ'])
            ->assertJsonMissing(['name' => 'Sản phẩm tạm ẩn']);
    }

    public function test_filter_products_by_category(): void
    {
        $cat1 = $this->createCategory(['name' => 'Tràng hạt', 'slug' => 'trang-hat']);
        $cat2 = $this->createCategory(['name' => 'Kinh sách', 'slug' => 'kinh-sach']);

        $this->createProduct($cat1, ['name' => 'Chuỗi gỗ sưa', 'slug' => 'chuoi-go-sua']);
        $this->createProduct($cat2, ['name' => 'Kinh Pháp Cú Bìa Cứng', 'slug' => 'kinh-phap-cu-bia-cung']);

        $response = $this->getJson('/api/v1/shop/products?category=trang-hat');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chuỗi gỗ sưa')
            ->assertJsonMissing(['name' => 'Kinh Pháp Cú Bìa Cứng']);
    }

    public function test_search_products_accent_insensitive(): void
    {
        $category = $this->createCategory();

        $this->createProduct($category, ['name' => 'Chuỗi Hạt Bồ Đề Kim Cang', 'slug' => 'chuoi-hat-bo-de-kim-cang']);
        $this->createProduct($category, ['name' => 'Lư Hương Gốm Sứ', 'slug' => 'lu-huong-gom-su']);

        // Search with plain non-accented text
        $response1 = $this->getJson('/api/v1/shop/products?q=chuoi+hat');
        $response1->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chuỗi Hạt Bồ Đề Kim Cang');

        // Search with Vietnamese accents
        $response2 = $this->getJson('/api/v1/shop/products?q=Bồ+Đề');
        $response2->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chuỗi Hạt Bồ Đề Kim Cang');
    }

    public function test_sort_products_by_price_and_latest(): void
    {
        $category = $this->createCategory();

        $cheap = $this->createProduct($category, [
            'name' => 'Chỉ ngũ sắc',
            'slug' => 'chi-ngu-sac',
            'price_vnd' => 20000,
            'published_at' => now()->subDays(2),
        ]);

        $expensive = $this->createProduct($category, [
            'name' => 'Chuông xoay Tây Tạng',
            'slug' => 'chuong-xoay-tay-tang',
            'price_vnd' => 1200000,
            'published_at' => now()->subDay(),
        ]);

        // Ascending price
        $resAsc = $this->getJson('/api/v1/shop/products?sort=price_asc');
        $resAsc->assertOk();
        $this->assertEquals('Chỉ ngũ sắc', $resAsc->json('data.0.name'));
        $this->assertEquals('Chuông xoay Tây Tạng', $resAsc->json('data.1.name'));

        // Descending price
        $resDesc = $this->getJson('/api/v1/shop/products?sort=price_desc');
        $resDesc->assertOk();
        $this->assertEquals('Chuông xoay Tây Tạng', $resDesc->json('data.0.name'));
        $this->assertEquals('Chỉ ngũ sắc', $resDesc->json('data.1.name'));

        // Latest
        $resLatest = $this->getJson('/api/v1/shop/products?sort=latest');
        $resLatest->assertOk();
        $this->assertEquals('Chuông xoay Tây Tạng', $resLatest->json('data.0.name'));
    }

    public function test_get_product_detail_success_and_404_on_draft(): void
    {
        $category = $this->createCategory();

        $product = $this->createProduct($category, [
            'name' => 'Khánh đồng tụng kinh',
            'slug' => 'khanh-dong-tung-kinh',
            'price_vnd' => 350000,
            'description' => 'Âm thanh ngân vang, thanh tịnh.',
        ]);

        $resSuccess = $this->getJson('/api/v1/shop/products/khanh-dong-tung-kinh');
        $resSuccess->assertOk()
            ->assertJsonPath('data.name', 'Khánh đồng tụng kinh')
            ->assertJsonPath('data.price_vnd', 350000)
            ->assertJsonPath('data.formatted_price', '350.000 ₫')
            ->assertJsonPath('data.description', 'Âm thanh ngân vang, thanh tịnh.');

        // Draft product returns 404
        $draft = Product::create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm thử nghiệm',
            'slug' => 'san-pham-thu-nghiem',
            'status' => ProductStatus::Draft,
        ]);

        $resDraft = $this->getJson('/api/v1/shop/products/san-pham-thu-nghiem');
        $resDraft->assertStatus(404);
    }

    public function test_availability_calculation_based_on_inventory_mode(): void
    {
        $category = $this->createCategory();

        $trackedInStock = $this->createProduct($category, [
            'name' => 'Còn hàng',
            'slug' => 'con-hang',
            'inventory_mode' => InventoryMode::Tracked,
            'stock_quantity' => 5,
        ]);
        $this->assertTrue($trackedInStock->isAvailable());

        $trackedOutOfStock = $this->createProduct($category, [
            'name' => 'Hết hàng',
            'slug' => 'het-hang',
            'inventory_mode' => InventoryMode::Tracked,
            'stock_quantity' => 0,
        ]);
        $this->assertFalse($trackedOutOfStock->isAvailable());

        $notTracked = $this->createProduct($category, [
            'name' => 'Không giới hạn',
            'slug' => 'khong-gioi-han',
            'inventory_mode' => InventoryMode::NotTracked,
            'stock_quantity' => 0,
        ]);
        $this->assertTrue($notTracked->isAvailable());
    }

    public function test_cannot_create_product_with_negative_price(): void
    {
        $category = $this->createCategory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Giá vật phẩm không thể là số âm.');

        $this->createProduct($category, [
            'name' => 'Giá âm',
            'slug' => 'gia-am',
            'price_vnd' => -5000,
        ]);
    }

    public function test_cannot_create_product_with_forbidden_superstitious_claims(): void
    {
        $category = $this->createCategory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Nội dung chứa cụm từ mê tín/thương mại hóa tâm linh không phù hợp triết lý Phật giáo: 'tăng công đức'");

        $this->createProduct($category, [
            'name' => 'Vòng đeo tay tăng công đức',
            'slug' => 'vong-tay-tang-cong-duc',
        ]);
    }

    public function test_guest_cannot_access_filament_products_admin(): void
    {
        $res = $this->get('/admin/products');
        $res->assertRedirect('/admin/login');

        $resCat = $this->get('/admin/product-categories');
        $resCat->assertRedirect('/admin/login');
    }

    public function test_public_product_response_does_not_leak_cost_or_create_practice_sessions(): void
    {
        $category = $this->createCategory();
        $product = $this->createProduct($category, [
            'name' => 'Kinh Bát Nhã Ba La Mật Đa',
            'slug' => 'kinh-bat-nha',
        ]);

        $res = $this->getJson("/api/v1/shop/products/{$product->slug}");

        $res->assertOk()
            ->assertJsonMissing(['cost_price'])
            ->assertJsonMissing(['supplier'])
            ->assertJsonMissing(['practice_session_id'])
            ->assertJsonMissing(['streak_count'])
            ->assertJsonMissing(['merit_points']);

        $this->assertDatabaseCount('practice_sessions', 0);
    }
}
