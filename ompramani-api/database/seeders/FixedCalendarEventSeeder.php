<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Calendar\FixedCalendarEventRegistry;
use Illuminate\Database\Seeder;

class FixedCalendarEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FixedCalendarEventRegistry::syncToDatabase();
    }
}
