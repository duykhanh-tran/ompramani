<?php

declare(strict_types=1);

namespace App\Enums;

enum PracticePlanStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';

    /**
     * Display label in Vietnamese.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang áp dụng',
            self::Paused => 'Đang tạm dừng',
            self::Archived => 'Đã lưu trữ',
        };
    }

    /**
     * Determine if this plan is actively generating future occurrences.
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
