<?php

namespace App\Filament\Resources\SutraVersions\Pages;

use App\Filament\Resources\SutraVersions\SutraVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSutraVersions extends ListRecords
{
    protected static string $resource = SutraVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
