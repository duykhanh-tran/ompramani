<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TraditionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('traditions')->insert([
            [
                'name' => 'Bắc Truyền',
                'slug' => 'bac-truyen',
                'description' => 'Phật giáo Bắc Truyền',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Nam Truyền',
                'slug' => 'nam-truyen',
                'description' => 'Phật giáo Nam Truyền',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
