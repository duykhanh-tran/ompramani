<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoCopyrightStatus: string
{
    case PendingReview = 'pending_review';
    case VerifiedEmbeddable = 'verified_embeddable';
    case LicensedHosted = 'licensed_hosted';
    case Restricted = 'restricted';
    case NotPermitted = 'not_permitted';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Đang kiểm tra bản quyền',
            self::VerifiedEmbeddable => 'Đã xác minh quyền nhúng',
            self::LicensedHosted => 'Được cấp phép lưu trữ',
            self::Restricted => 'Hạn chế sử dụng',
            self::NotPermitted => 'Không được phép phổ biến',
        };
    }
}
