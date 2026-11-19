<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Sepay = 'sepay';
    case Vnpay = 'vnpay';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Thanh toán khi nhận hàng (COD)',
            self::Sepay => 'Thanh toán SePay (VietQR)',
            self::Vnpay => 'VNPAY (phương thức cũ)',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::Cod || $this === self::Sepay;
    }
}
