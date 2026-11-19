<?php

declare(strict_types=1);

namespace App\Services\Shop;

use App\Enums\InventoryMode;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ValidationException;

class InventoryService
{
    /**
     * Atomically adjust stock of a tracked product to a specific quantity (e.g. stocktake / manual set).
     *
     * @throws InvalidArgumentException
     */
    public function adjustStock(
        Product $product,
        int $newQuantity,
        User $actor,
        string $reason,
        string $type = 'adjustment'
    ): InventoryMovement {
        if ($newQuantity < 0) {
            throw new InvalidArgumentException('Số lượng tồn kho không thể là số âm.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Bắt buộc nhập lý do điều chỉnh tồn kho.');
        }

        return DB::transaction(function () use ($product, $newQuantity, $actor, $reason, $type): InventoryMovement {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::query()
                ->where('id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedProduct->inventory_mode !== InventoryMode::Tracked) {
                throw new InvalidArgumentException("Vật phẩm '{$lockedProduct->name}' thuộc chế độ không theo dõi tồn kho.");
            }

            $quantityBefore = (int) $lockedProduct->stock_quantity;
            $change = $newQuantity - $quantityBefore;

            // Update product stock without altering price or other attributes
            $lockedProduct->stock_quantity = $newQuantity;
            $lockedProduct->save();

            // Record audit movement
            return InventoryMovement::create([
                'product_id' => $lockedProduct->id,
                'type' => $type,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $newQuantity,
                'change' => $change,
                'reason' => $reason,
                'actor_type' => 'admin',
                'actor_id' => $actor->id,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Atomically increase stock by a given quantity (e.g. restock / nhập thêm hàng).
     */
    public function incrementStock(
        Product $product,
        int $quantityToAdd,
        User $actor,
        string $reason
    ): InventoryMovement {
        if ($quantityToAdd <= 0) {
            throw new InvalidArgumentException('Số lượng nhập thêm phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($product, $quantityToAdd, $actor, $reason): InventoryMovement {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::query()
                ->where('id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newQuantity = (int) $lockedProduct->stock_quantity + $quantityToAdd;

            return $this->adjustStock($lockedProduct, $newQuantity, $actor, $reason, 'restock');
        });
    }

    /**
     * Atomically decrease stock by a given quantity (e.g. damaged goods / xuất bớt hàng).
     */
    public function decrementStock(
        Product $product,
        int $quantityToDeduct,
        User $actor,
        string $reason
    ): InventoryMovement {
        if ($quantityToDeduct <= 0) {
            throw new InvalidArgumentException('Số lượng xuất giảm phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($product, $quantityToDeduct, $actor, $reason): InventoryMovement {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::query()
                ->where('id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStock = (int) $lockedProduct->stock_quantity;
            if ($quantityToDeduct > $currentStock) {
                throw new InvalidArgumentException("Không thể giảm {$quantityToDeduct} vật phẩm vì tồn kho hiện tại chỉ còn {$currentStock}.");
            }

            $newQuantity = $currentStock - $quantityToDeduct;

            return $this->adjustStock($lockedProduct, $newQuantity, $actor, $reason, 'adjustment');
        });
    }
}
