<?php

declare(strict_types=1);

namespace App\Enums;

enum TempleCoordinateAccuracy: string
{
    case ExactVerified = 'exact_verified';
    case Approximate = 'approximate';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::ExactVerified => 'Tọa độ chính xác đã xác minh',
            self::Approximate => 'Tọa độ ước chừng khu vực',
            self::Unavailable => 'Không có tọa độ',
        };
    }
}
