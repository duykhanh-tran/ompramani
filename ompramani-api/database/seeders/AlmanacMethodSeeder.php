<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AlmanacMethod;
use App\Services\Almanac\Providers\ThanhLongAlmanacProvider;
use Illuminate\Database\Seeder;

class AlmanacMethodSeeder extends Seeder
{
    /**
     * Seed the initial verified almanac method.
     */
    public function run(): void
    {
        AlmanacMethod::firstOrCreate(
            ['key' => ThanhLongAlmanacProvider::KEY],
            [
                'name' => ThanhLongAlmanacProvider::NAME,
                'description' => 'Phương pháp tính ngày và giờ Hoàng đạo / Hắc đạo dựa trên Thập Nhị Thần Sát và Can Chi truyền thống Việt Nam.',
                'version' => ThanhLongAlmanacProvider::VERSION,
                'source_title' => 'Hiệp Kỷ Biện Phương Thư / Lịch Vạn Niên Việt Nam',
                'source_url' => null,
                'license_details' => 'Public Domain / Traditional Folk Heritage',
                'calendar_timezone' => ThanhLongAlmanacProvider::TIMEZONE,
                'supported_from' => ThanhLongAlmanacProvider::MIN_SUPPORTED_DATE,
                'supported_to' => ThanhLongAlmanacProvider::MAX_SUPPORTED_DATE,
                'is_primary' => true,
                'status' => 'active',
            ]
        );
    }
}
