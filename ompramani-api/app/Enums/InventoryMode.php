<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryMode: string
{
    case NotTracked = 'not_tracked';
    case Tracked = 'tracked';

    public function label(): string
    {
        return match ($this) {
            self::NotTracked => 'Không theo dõi tồn kho',
            self::Tracked => 'Theo dõi số lượng tồn kho',
        };
    }
}
