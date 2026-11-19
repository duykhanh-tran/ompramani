<?php

namespace App\Filament\Resources\Sutras\Pages;

use App\Filament\Resources\Sutras\SutraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSutras extends ListRecords
{
    protected static string $resource = SutraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
