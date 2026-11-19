<?php

declare(strict_types=1);

namespace App\Services\Shop;

class ShippingFeePolicy
{
    public const FLAT_SHIPPING_FEE_VND = 30000;

    public const FREE_SHIPPING_THRESHOLD_VND = 500000;

    /**
     * Calculate authoritative shipping fee based on order subtotal.
     * Orders >= 500.000 VND enjoy free shipping, otherwise 30.000 VND flat rate.
     */
    public static function calculateFee(int $subtotalVnd): int
    {
        if ($subtotalVnd <= 0) {
            return 0;
        }

        if ($subtotalVnd >= self::FREE_SHIPPING_THRESHOLD_VND) {
            return 0;
        }

        return self::FLAT_SHIPPING_FEE_VND;
    }
}
