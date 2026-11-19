<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'public_id',
        'order_number',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'shipping_province',
        'shipping_fee_vnd',
        'subtotal_vnd',
        'total_vnd',
        'currency',
        'order_status',
        'payment_method',
        'payment_status',
        'notes',
        'internal_notes',
        'cancellation_reason',
        'cancelled_at',
        'completed_at',
        'paid_at',
        'idempotency_key',
        'placed_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'shipping_fee_vnd' => 'integer',
        'subtotal_vnd' => 'integer',
        'total_vnd' => 'integer',
        'order_status' => OrderStatus::class,
        'payment_method' => PaymentMethod::class,
        'payment_status' => PaymentStatus::class,
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
        'placed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->public_id)) {
                $order->public_id = (string) Str::ulid();
            }

            if (empty($order->order_number)) {
                $order->order_number = static::generateUniqueOrderNumber();
            }

            if (empty($order->placed_at)) {
                $order->placed_at = now();
            }
        });
    }

    public static function generateUniqueOrderNumber(): string
    {
        do {
            $number = 'OPM-'.date('Ymd').'-'.strtoupper(Str::random(6));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id')->orderBy('created_at', 'asc');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class, 'order_id')->orderBy('created_at', 'desc');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'order_id')->orderBy('created_at', 'asc');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(OrderPayment::class, 'order_id')->latestOfMany();
    }

    /**
     * Determine if customer is permitted to directly cancel this order.
     * Allowed only for COD orders currently in Pending status.
     */
    public function canBeCancelledByCustomer(): bool
    {
        return $this->order_status === OrderStatus::Pending
            && $this->payment_method === PaymentMethod::Cod
            && $this->payment_status !== PaymentStatus::Paid;
    }

    /**
     * Check if transition to target status is valid according to the State Machine.
     */
    public function canTransitionTo(OrderStatus $target): bool
    {
        if ($this->order_status === $target) {
            return false;
        }

        return match ($this->order_status) {
            OrderStatus::Pending => in_array($target, [OrderStatus::Confirmed, OrderStatus::Cancelled], true),
            OrderStatus::Confirmed => in_array($target, [OrderStatus::Processing, OrderStatus::Cancelled], true),
            OrderStatus::Processing => $target === OrderStatus::Completed,
            OrderStatus::Completed => false, // Terminal
            OrderStatus::Cancelled => false, // Terminal
        };
    }

    public function formattedSubtotal(): string
    {
        return number_format($this->subtotal_vnd, 0, ',', '.').' ₫';
    }

    public function formattedShippingFee(): string
    {
        if ($this->shipping_fee_vnd === 0) {
            return 'Miễn phí';
        }

        return number_format($this->shipping_fee_vnd, 0, ',', '.').' ₫';
    }

    public function formattedTotal(): string
    {
        return number_format($this->total_vnd, 0, ',', '.').' ₫';
    }
}
