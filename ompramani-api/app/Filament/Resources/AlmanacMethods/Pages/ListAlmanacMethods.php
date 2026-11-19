<?php

declare(strict_types=1);

namespace App\Filament\Resources\AlmanacMethods\Pages;

use App\Filament\Resources\AlmanacMethods\AlmanacMethodResource;
use Filament\Resources\Pages\ListRecords;

class ListAlmanacMethods extends ListRecords
{
    protected static string $resource = AlmanacMethodResource::class;
}
