<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrderRefund extends Model
{
    use HasFactory;

    protected $table = 'order_refunds';

    protected $fillable = [
        'order_id',
        'refund_number',
        'amount_vnd',
        'status',
        'reason',
        'actor_id',
        'refunded_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'amount_vnd' => 'integer',
        'actor_id' => 'integer',
        'refunded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (OrderRefund $refund): void {
            if (empty($refund->refund_number)) {
                $refund->refund_number = 'REF-'.date('Ymd').'-'.strtoupper(Str::random(6));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function formattedAmount(): string
    {
        return number_format($this->amount_vnd, 0, ',', '.').' ₫';
    }
}
