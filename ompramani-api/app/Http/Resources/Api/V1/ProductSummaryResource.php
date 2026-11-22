<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use App\Services\Media\MediaUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Product
 */
class ProductSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageUrl = MediaUrlResolver::resolve($this->cover_image);

        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'short_description' => $this->short_description,
            'price_vnd' => $this->price_vnd,
            'formatted_price' => $this->formattedPrice(),
            'cover_image' => $imageUrl,
            'inventory_mode' => $this->inventory_mode?->value ?? (string) $this->inventory_mode,
            'is_available' => $this->isAvailable(),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
