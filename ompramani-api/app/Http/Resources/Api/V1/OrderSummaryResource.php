<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @mixin Order
 */
class OrderSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OrderItem|null $firstItem */
        $firstItem = $this->relationLoaded('items') ? $this->items->first() : null;

        $firstItemImage = null;
        if (! empty($firstItem?->product_image_snapshot)) {
            if (Str::startsWith($firstItem->product_image_snapshot, ['http://', 'https://', '/'])) {
                $firstItemImage = $firstItem->product_image_snapshot;
            } else {
                $publicDisk = (string) config('filesystems.public_disk', 'public');
                $firstItemImage = Storage::disk($publicDisk)->url($firstItem->product_image_snapshot);
            }
        }

        return [
            'public_id' => $this->public_id,
            'order_number' => $this->order_number,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'order_status' => $this->order_status?->value ?? (string) $this->order_status,
            'order_status_label' => $this->order_status?->label() ?? (string) $this->order_status,
            'payment_method' => $this->payment_method?->value ?? (string) $this->payment_method,
            'payment_method_label' => $this->payment_method?->label() ?? (string) $this->payment_method,
            'payment_status' => $this->payment_status?->value ?? (string) $this->payment_status,
            'payment_status_label' => $this->payment_status?->label() ?? (string) $this->payment_status,
            'total_vnd' => $this->total_vnd,
            'formatted_total' => $this->formattedTotal(),
            'item_count' => $this->relationLoaded('items') ? $this->items->sum('quantity') : 0,
            'first_item_name' => $firstItem?->product_name_snapshot,
            'first_item_thumbnail' => $firstItemImage,
            'can_cancel' => $this->canBeCancelledByCustomer(),
        ];
    }
}
