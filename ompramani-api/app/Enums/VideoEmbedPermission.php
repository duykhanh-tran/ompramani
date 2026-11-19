<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoEmbedPermission: string
{
    case Approved = 'approved';
    case ExternalOnly = 'external_only';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Được phép nhúng',
            self::ExternalOnly => 'Chỉ mở liên kết ngoài',
            self::Revoked => 'Thu hồi quyền nhúng',
        };
    }
}
