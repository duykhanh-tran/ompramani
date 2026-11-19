<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\InventoryMode;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductCategoryResource;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductSummaryResource;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopProductController extends Controller
{
    /**
     * Get active product categories for catalog browsing.
     */
    public function categories(): JsonResponse
    {
        $categories = ProductCategory::query()
            ->active()
            ->withCount(['products' => function ($q) {
                $q->published();
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => ProductCategoryResource::collection($categories),
        ]);
    }

    /**
     * List and search published Buddhist products.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'in:latest,price_asc,price_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Product::query()
            ->published()
            ->with('category');

        if ($request->filled('category')) {
            $query->byCategory($request->input('category'));
        }

        if ($request->filled('q')) {
            $query->search($request->input('q'));
        }

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('price_vnd', 'asc')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('price_vnd')->orderByDesc('id'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $perPage = (int) $request->input('per_page', 12);
        $products = $query->paginate($perPage);

        return ProductSummaryResource::collection($products);
    }

    /**
     * Get public detail of a published Buddhist product.
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->published()
            ->with('category')
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                    ->orWhere('public_id', $slug);
            })
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Vật phẩm không tồn tại hoặc chưa được phát hành.',
            ], 404);
        }

        return response()->json([
            'data' => new ProductDetailResource($product),
        ]);
    }

    /**
     * Preview and validate shopping cart items against live database prices and stock.
     * Strictly read-only: does not write orders, reservations, or affect practice sessions.
     */
    public function previewCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        // Aggregate / deduplicate multiple occurrences of same product_id in request
        $rawItems = [];
        foreach ($validated['items'] as $item) {
            $id = trim((string) $item['product_id']);
            $qty = (int) $item['quantity'];
            if (! isset($rawItems[$id])) {
                $rawItems[$id] = 0;
            }
            $rawItems[$id] = min(99, $rawItems[$id] + $qty);
        }

        $productIdentifiers = array_keys($rawItems);

        // Fetch products matching public_id or slug that are published and active
        $products = Product::query()
            ->published()
            ->where(function ($q) use ($productIdentifiers) {
                $q->whereIn('public_id', $productIdentifiers)
                    ->orWhereIn('slug', $productIdentifiers);
            })
            ->get();

        $previewItems = [];
        $subtotalVnd = 0;
        $totalQuantity = 0;
        $allValid = true;

        foreach ($rawItems as $identifier => $requestedQuantity) {
            /** @var Product|null $product */
            $product = $products->first(function (Product $p) use ($identifier) {
                return $p->public_id === $identifier || $p->slug === $identifier;
            });

            if (! $product) {
                $previewItems[] = [
                    'product_id' => $identifier,
                    'slug' => null,
                    'name' => 'Sản phẩm không còn khả dụng',
                    'cover_image' => null,
                    'unit_price_vnd' => 0,
                    'formatted_unit_price' => '0 ₫',
                    'quantity' => $requestedQuantity,
                    'line_subtotal_vnd' => 0,
                    'formatted_line_subtotal' => '0 ₫',
                    'is_available' => false,
                    'error' => 'product_unavailable',
                    'error_message' => 'Vật phẩm không tồn tại, đã lưu trữ hoặc ngừng phát hành.',
                    'inventory_mode' => 'not_tracked',
                    'available_stock' => 0,
                ];
                $allValid = false;

                continue;
            }

            // Image URL resolution
            $imageUrl = null;
            if (! empty($product->cover_image)) {
                if (Str::startsWith($product->cover_image, ['http://', 'https://', '/'])) {
                    $imageUrl = $product->cover_image;
                } else {
                    $publicDisk = (string) config('filesystems.public_disk', 'public');
                    $imageUrl = Storage::disk($publicDisk)->url($product->cover_image);
                }
            }

            $unitPrice = (int) ($product->price_vnd ?? 0);
            $isAvailable = true;
            $errorCode = null;
            $errorMessage = null;

            if ($product->inventory_mode === InventoryMode::Tracked) {
                if ($product->stock_quantity <= 0) {
                    $isAvailable = false;
                    $errorCode = 'out_of_stock';
                    $errorMessage = 'Vật phẩm tạm thời hết hàng.';
                } elseif ($requestedQuantity > $product->stock_quantity) {
                    $isAvailable = false;
                    $errorCode = 'insufficient_stock';
                    $errorMessage = "Số lượng trong kho không đủ (chỉ còn {$product->stock_quantity} vật phẩm).";
                }
            }

            $lineSubtotal = $isAvailable ? ($unitPrice * $requestedQuantity) : 0;

            if ($isAvailable) {
                $subtotalVnd += $lineSubtotal;
                $totalQuantity += $requestedQuantity;
            } else {
                $allValid = false;
            }

            $previewItems[] = [
                'product_id' => $product->public_id,
                'slug' => $product->slug,
                'name' => $product->name,
                'cover_image' => $imageUrl,
                'unit_price_vnd' => $unitPrice,
                'formatted_unit_price' => number_format($unitPrice, 0, ',', '.').' ₫',
                'quantity' => $requestedQuantity,
                'line_subtotal_vnd' => $lineSubtotal,
                'formatted_line_subtotal' => number_format($lineSubtotal, 0, ',', '.').' ₫',
                'is_available' => $isAvailable,
                'error' => $errorCode,
                'error_message' => $errorMessage,
                'inventory_mode' => $product->inventory_mode?->value ?? 'not_tracked',
                'available_stock' => $product->inventory_mode === InventoryMode::Tracked ? $product->stock_quantity : null,
            ];
        }

        return response()->json([
            'data' => [
                'items' => $previewItems,
                'subtotal_vnd' => $subtotalVnd,
                'formatted_subtotal' => number_format($subtotalVnd, 0, ',', '.').' ₫',
                'total_quantity' => $totalQuantity,
                'is_valid' => $allValid && count($previewItems) > 0,
            ],
        ]);
    }
}
