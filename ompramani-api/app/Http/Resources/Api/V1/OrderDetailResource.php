<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusHistories = $this->relationLoaded('statusHistories') ? $this->statusHistories : $this->statusHistories()->get();

        // Build customer-safe timeline
        $timeline = [];
        $timeline[] = [
            'key' => 'placed',
            'label' => 'Đặt hàng thành công',
            'timestamp' => $this->placed_at?->toIso8601String(),
            'is_current' => $this->order_status === OrderStatus::Pending,
            'is_passed' => true,
        ];

        if ($this->order_status === OrderStatus::Cancelled) {
            $timeline[] = [
                'key' => 'cancelled',
                'label' => 'Đã hủy đơn hàng',
                'timestamp' => $this->cancelled_at?->toIso8601String(),
                'is_current' => true,
                'is_passed' => true,
                'reason' => $this->cancellation_reason,
            ];
        } else {
            /** @var OrderStatusHistory|null $confirmedEvent */
            $confirmedEvent = $statusHistories->first(fn (OrderStatusHistory $h) => $h->to_status === OrderStatus::Confirmed);
            /** @var OrderStatusHistory|null $processingEvent */
            $processingEvent = $statusHistories->first(fn (OrderStatusHistory $h) => $h->to_status === OrderStatus::Processing);
            /** @var OrderStatusHistory|null $completedEvent */
            $completedEvent = $statusHistories->first(fn (OrderStatusHistory $h) => $h->to_status === OrderStatus::Completed);

            $isConfirmedPassed = in_array($this->order_status, [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Completed], true);
            $isProcessingPassed = in_array($this->order_status, [OrderStatus::Processing, OrderStatus::Completed], true);
            $isCompletedPassed = $this->order_status === OrderStatus::Completed;

            $timeline[] = [
                'key' => 'confirmed',
                'label' => 'Đã xác nhận',
                'timestamp' => $confirmedEvent?->created_at?->toIso8601String(),
                'is_current' => $this->order_status === OrderStatus::Confirmed,
                'is_passed' => $isConfirmedPassed,
            ];

            $timeline[] = [
                'key' => 'processing',
                'label' => 'Đang chuẩn bị gói hàng',
                'timestamp' => $processingEvent?->created_at?->toIso8601String(),
                'is_current' => $this->order_status === OrderStatus::Processing,
                'is_passed' => $isProcessingPassed,
            ];

            $timeline[] = [
                'key' => 'completed',
                'label' => 'Hoàn tất giao nhận',
                'timestamp' => $this->completed_at?->toIso8601String() ?? $completedEvent?->created_at?->toIso8601String(),
                'is_current' => $this->order_status === OrderStatus::Completed,
                'is_passed' => $isCompletedPassed,
            ];
        }

        return [
            'public_id' => $this->public_id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'shipping_address' => $this->shipping_address,
            'shipping_province' => $this->shipping_province,
            'shipping_fee_vnd' => $this->shipping_fee_vnd,
            'formatted_shipping_fee' => $this->formattedShippingFee(),
            'subtotal_vnd' => $this->subtotal_vnd,
            'formatted_subtotal' => $this->formattedSubtotal(),
            'total_vnd' => $this->total_vnd,
            'formatted_total' => $this->formattedTotal(),
            'currency' => $this->currency,
            'order_status' => $this->order_status?->value ?? (string) $this->order_status,
            'order_status_label' => $this->order_status?->label() ?? (string) $this->order_status,
            'payment_method' => $this->payment_method?->value ?? (string) $this->payment_method,
            'payment_method_label' => $this->payment_method?->label() ?? (string) $this->payment_method,
            'payment_status' => $this->payment_status?->value ?? (string) $this->payment_status,
            'payment_status_label' => $this->payment_status?->label() ?? (string) $this->payment_status,
            'notes' => $this->notes,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'can_cancel' => $this->canBeCancelledByCustomer(),
            'can_retry_payment' => false,
            'can_switch_to_cod' => $this->payment_method !== \App\Enums\PaymentMethod::Cod && $this->payment_status !== \App\Enums\PaymentStatus::Paid && $this->order_status !== OrderStatus::Cancelled,
            'latest_payment' => $this->relationLoaded('latestPayment') && $this->latestPayment ? [
                'attempt_number' => $this->latestPayment->attempt_number,
                'status' => $this->latestPayment->status?->value ?? (string) $this->latestPayment->status,
                'status_label' => $this->latestPayment->status?->label() ?? (string) $this->latestPayment->status,
                'transaction_ref' => $this->latestPayment->transaction_ref ?? $this->latestPayment->vnp_txn_ref,
                'bank_code' => $this->latestPayment->bank_code ?? $this->latestPayment->vnp_bank_code,
                'vnp_txn_ref' => $this->latestPayment->vnp_txn_ref,
                'vnp_transaction_no' => $this->latestPayment->vnp_transaction_no,
                'vnp_bank_code' => $this->latestPayment->vnp_bank_code,
                'paid_at' => $this->latestPayment->paid_at?->toIso8601String(),
            ] : null,
            'status_timeline' => $timeline,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
