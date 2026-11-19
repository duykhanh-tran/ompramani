<?php

declare(strict_types=1);

namespace App\Enums;

enum PracticeReminderDeliveryStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Sent = 'sent';
    case SkippedQuietHours = 'skipped_quiet_hours';
    case SkippedDisabled = 'skipped_disabled';
    case SkippedLate = 'skipped_late';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xử lý',
            self::Processing => 'Đang xử lý',
            self::Sent => 'Đã gửi',
            self::SkippedQuietHours => 'Bỏ qua (Giờ yên tĩnh)',
            self::SkippedDisabled => 'Bỏ qua (Đã tắt)',
            self::SkippedLate => 'Bỏ qua (Quá hạn)',
            self::Failed => 'Lỗi gửi',
        };
    }
}
