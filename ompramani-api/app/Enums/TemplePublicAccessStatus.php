<?php

declare(strict_types=1);

namespace App\Enums;

enum TemplePublicAccessStatus: string
{
    case PubliclyAccessible = 'publicly_accessible';
    case Restricted = 'restricted';
    case ByAppointment = 'by_appointment';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::PubliclyAccessible => 'Mở cửa công khai',
            self::Restricted => 'Hạn chế vào cửa',
            self::ByAppointment => 'Cần liên hệ / hẹn trước',
            self::Unknown => 'Chưa rõ thông tin',
        };
    }
}
