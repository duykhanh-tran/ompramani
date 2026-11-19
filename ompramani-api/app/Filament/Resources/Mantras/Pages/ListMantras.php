<?php

namespace App\Filament\Resources\Mantras\Pages;

use App\Filament\Resources\Mantras\MantraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMantras extends ListRecords
{
    protected static string $resource = MantraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
