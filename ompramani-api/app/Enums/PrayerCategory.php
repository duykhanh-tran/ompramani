<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerCategory: string
{
    case BinhAn = 'binh_an';
    case GiaDinh = 'gia_dinh';
    case TriAn = 'tri_an';
    case HoiHuong = 'hoi_huong';
    case ThienLanh = 'thien_lanh';
    case Khac = 'khac';

    public function label(): string
    {
        return match ($this) {
            self::BinhAn => 'Bình an & Cầu an',
            self::GiaDinh => 'Gia đình & Thân quyến',
            self::TriAn => 'Tri ân & Báo ân',
            self::HoiHuong => 'Hồi hướng công đức',
            self::ThienLanh => 'Lời nguyện thiện lành',
            self::Khac => 'Khác',
        };
    }
}
