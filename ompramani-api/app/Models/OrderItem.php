<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_public_id',
        'product_name_snapshot',
        'product_sku_snapshot',
        'product_image_snapshot',
        'unit_price_vnd',
        'quantity',
        'line_total_vnd',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'unit_price_vnd' => 'integer',
        'quantity' => 'integer',
        'line_total_vnd' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function formattedUnitPrice(): string
    {
        return number_format($this->unit_price_vnd, 0, ',', '.').' ₫';
    }

    public function formattedLineTotal(): string
    {
        return number_format($this->line_total_vnd, 0, ',', '.').' ₫';
    }
}
