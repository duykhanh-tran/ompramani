<?php

declare(strict_types=1);

namespace App\Services\Shop;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ShopOrderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    /**
     * Perform an atomic, verified order status transition.
     *
     * @throws ValidationException
     */
    public function transition(
        Order $order,
        OrderStatus $targetStatus,
        ?User $actor,
        string $actorType,
        ?string $reason = null,
        ?string $notes = null,
        bool $codCollected = false
    ): Order {
        return DB::transaction(function () use ($order, $targetStatus, $actor, $actorType, $reason, $notes, $codCollected): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->where('id', $order->id)
                ->lockForUpdate()
                ->with('items')
                ->firstOrFail();

            $fromStatus = $lockedOrder->order_status;

            // Idempotency check: if already at target status, return without duplicate actions
            if ($fromStatus === $targetStatus) {
                return $lockedOrder;
            }

            // State Machine check
            if (! $lockedOrder->canTransitionTo($targetStatus)) {
                throw ValidationException::withMessages([
                    'order_status' => "Không thể chuyển đơn hàng từ trạng thái '{$fromStatus->label()}' sang '{$targetStatus->label()}'.",
                ]);
            }

            // Specific rules when cancelling
            if ($targetStatus === OrderStatus::Cancelled) {
                if ($lockedOrder->payment_status === PaymentStatus::Paid) {
                    throw ValidationException::withMessages([
                        'payment_status' => 'Không thể hủy trực tiếp đơn hàng đã thanh toán khi chưa có quy trình hoàn tiền (refund). Vui lòng liên hệ quản trị viên.',
                    ]);
                }

                // Restore stock atomically for tracked products (only once)
                foreach ($lockedOrder->items as $item) {
                    if ($item->product_id) {
                        /** @var Product|null $product */
                        $product = Product::query()
                            ->where('id', $item->product_id)
                            ->lockForUpdate()
                            ->first();

                        if ($product && $product->inventory_mode === InventoryMode::Tracked) {
                            $before = (int) $product->stock_quantity;
                            $product->increment('stock_quantity', $item->quantity);

                            InventoryMovement::create([
                                'product_id' => $product->id,
                                'type' => 'order_cancelled',
                                'quantity_before' => $before,
                                'quantity_after' => $before + $item->quantity,
                                'change' => $item->quantity,
                                'reason' => "Hoàn kho do hủy đơn hàng #{$lockedOrder->order_number}".($reason ? ": {$reason}" : ''),
                                'actor_type' => $actorType,
                                'actor_id' => $actor?->id,
                                'created_at' => now(),
                            ]);
                        }
                    }
                }

                $lockedOrder->cancelled_at = now();
                $lockedOrder->cancellation_reason = $reason;
            }

            // Specific rules when completing
            if ($targetStatus === OrderStatus::Completed) {
                $lockedOrder->completed_at = now();

                // If COD collection is confirmed at completion, mark as Paid
                if ($codCollected && $lockedOrder->payment_method === PaymentMethod::Cod && $lockedOrder->payment_status !== PaymentStatus::Paid) {
                    $lockedOrder->payment_status = PaymentStatus::Paid;
                    $lockedOrder->paid_at = now();
                }
            }

            // Append admin internal note if provided
            if ($notes && $actorType === 'admin') {
                $timestamp = now()->format('d/m/Y H:i');
                $noteEntry = "[{$timestamp} - {$actor?->name}] {$notes}";
                $lockedOrder->internal_notes = $lockedOrder->internal_notes
                    ? $lockedOrder->internal_notes."\n".$noteEntry
                    : $noteEntry;
            }

            // Update order status
            $lockedOrder->order_status = $targetStatus;
            $lockedOrder->save();

            // Record audit trail
            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $fromStatus,
                'to_status' => $targetStatus,
                'actor_type' => $actorType,
                'actor_id' => $actor?->id,
                'reason' => $reason,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            // Notify customer safely after transaction commit
            DB::afterCommit(function () use ($lockedOrder, $fromStatus, $targetStatus): void {
                if ($lockedOrder->user) {
                    try {
                        $lockedOrder->user->notify(new ShopOrderNotification($lockedOrder, $fromStatus, $targetStatus));
                    } catch (\Throwable $e) {
                        // Suppress notification delivery failures from breaking transaction result
                        report($e);
                    }
                }
            });

            return $lockedOrder;
        });
    }

    /**
     * Admin confirms order (Pending -> Confirmed).
     */
    public function confirm(Order $order, User $actor, ?string $notes = null): Order
    {
        return $this->transition($order, OrderStatus::Confirmed, $actor, 'admin', null, $notes);
    }

    /**
     * Admin starts fulfillment (Confirmed -> Processing).
     */
    public function startProcessing(Order $order, User $actor, ?string $notes = null): Order
    {
        return $this->transition($order, OrderStatus::Processing, $actor, 'admin', null, $notes);
    }

    /**
     * Admin marks order complete (Processing -> Completed).
     */
    public function complete(Order $order, User $actor, ?string $notes = null, bool $codCollected = false): Order
    {
        return $this->transition($order, OrderStatus::Completed, $actor, 'admin', null, $notes, $codCollected);
    }

    /**
     * Cancel order (from Pending or Confirmed to Cancelled).
     */
    public function cancel(
        Order $order,
        ?User $actor,
        string $actorType,
        ?string $reason = null,
        ?string $notes = null
    ): Order {
        return $this->transition($order, OrderStatus::Cancelled, $actor, $actorType, $reason, $notes);
    }
}
