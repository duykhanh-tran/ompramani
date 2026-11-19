<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    use HasFactory;

    protected $table = 'order_payments';

    protected $fillable = [
        'order_id',
        'attempt_number',
        'payment_method',
        'provider',
        'amount_vnd',
        'currency',
        'status',
        'transaction_ref',
        'bank_code',
        'bank_transaction_no',
        'vnp_txn_ref',
        'vnp_transaction_no',
        'vnp_bank_code',
        'vnp_bank_tran_no',
        'vnp_card_type',
        'vnp_order_info',
        'vnp_pay_date',
        'vnp_response_code',
        'vnp_transaction_status',
        'vnp_secure_hash',
        'raw_payload',
        'ip_address',
        'paid_at',
        'failed_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'attempt_number' => 'integer',
        'amount_vnd' => 'integer',
        'payment_method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'raw_payload' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }
}
