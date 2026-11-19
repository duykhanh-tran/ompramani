<?php

namespace App\Enums;

enum ImportSectionType: string
{
    case Heading = 'heading';
    case Paragraph = 'paragraph';

    public function label(): string
    {
        return match ($this) {
            self::Heading => 'Tiêu đề (Heading)',
            self::Paragraph => 'Đoạn văn (Paragraph)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Heading => 'primary',
            self::Paragraph => 'gray',
        };
    }
}
