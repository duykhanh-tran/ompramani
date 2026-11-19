<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerVisibility: string
{
    case Private = 'private';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Riêng tư',
            self::Public => 'Chia sẻ cộng đồng',
        };
    }
}
