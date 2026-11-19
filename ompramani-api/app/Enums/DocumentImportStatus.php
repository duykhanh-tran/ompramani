<?php

namespace App\Enums;

enum DocumentImportStatus: string
{
    case Uploaded = 'uploaded';
    case Processing = 'processing';
    case Review = 'review';
    case Imported = 'imported';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Đã tải lên',
            self::Processing => 'Đang xử lý',
            self::Review => 'Chờ duyệt',
            self::Imported => 'Đã import',
            self::Failed => 'Lỗi xử lý',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Uploaded => 'gray',
            self::Processing => 'warning',
            self::Review => 'info',
            self::Imported => 'success',
            self::Failed => 'danger',
        };
    }
}
