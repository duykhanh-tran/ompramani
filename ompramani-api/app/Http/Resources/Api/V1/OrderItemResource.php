<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\OrderItem;
use App\Services\Media\MediaUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageUrl = filled($this->product_image_snapshot)
            ? MediaUrlResolver::resolve($this->product_image_snapshot)
            : null;

        return [
            'id' => $this->id,
            'product_public_id' => $this->product_public_id,
            'product_name' => $this->product_name_snapshot,
            'product_sku' => $this->product_sku_snapshot,
            'product_image' => $imageUrl,
            'unit_price_vnd' => $this->unit_price_vnd,
            'formatted_unit_price' => $this->formattedUnitPrice(),
            'quantity' => $this->quantity,
            'line_total_vnd' => $this->line_total_vnd,
            'formatted_line_total' => $this->formattedLineTotal(),
        ];
    }
}
