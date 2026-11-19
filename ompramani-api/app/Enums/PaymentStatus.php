<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case NeedsReconciliation = 'needs_reconciliation';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Chưa thanh toán',
            self::Pending => 'Chờ xử lý',
            self::Paid => 'Đã thanh toán',
            self::Failed => 'Thất bại',
            self::Cancelled => 'Đã hủy',
            self::Expired => 'Đã hết hạn',
            self::NeedsReconciliation => 'Cần đối soát',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'gray',
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'danger',
            self::Expired => 'gray',
            self::NeedsReconciliation => 'warning',
        };
    }
}
