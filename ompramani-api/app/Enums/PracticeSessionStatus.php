<?php

namespace App\Enums;

enum PracticeSessionStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /**
     * Display label in Vietnamese.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang tu tập',
            self::Paused => 'Đang tạm dừng',
            self::Completed => 'Đã hoàn thành',
            self::Cancelled => 'Đã hủy',
            self::Expired => 'Đã hết hạn',
        };
    }

    /**
     * Determine if this is an open (ongoing) status.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Active, self::Paused], true);
    }

    /**
     * Determine if this is a terminal (final) status.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Expired], true);
    }

    /**
     * Check if transition to a target status is valid.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Active => in_array($target, [self::Paused, self::Completed, self::Cancelled, self::Expired], true),
            self::Paused => in_array($target, [self::Active, self::Completed, self::Cancelled, self::Expired], true),
            self::Completed, self::Cancelled, self::Expired => false,
        };
    }
}
