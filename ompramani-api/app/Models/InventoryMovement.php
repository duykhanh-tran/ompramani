<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'inventory_movements';

    protected $fillable = [
        'product_id',
        'type',
        'quantity_before',
        'quantity_after',
        'change',
        'reason',
        'actor_type',
        'actor_id',
        'created_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'change' => 'integer',
        'actor_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'restock' => 'Nhập thêm hàng',
            'stocktake' => 'Kiểm kê định kỳ',
            'adjustment' => 'Điều chỉnh thủ công',
            'order_placed' => 'Đơn hàng mới',
            'order_cancelled' => 'Hoàn kho hủy đơn',
            default => $this->type,
        };
    }
}
