<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temples\Pages;

use App\Filament\Resources\Temples\TempleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTemple extends CreateRecord
{
    protected static string $resource = TempleResource::class;
}
