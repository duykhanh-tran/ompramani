<?php

namespace App\Filament\Resources\Mantras\Pages;

use App\Filament\Resources\Mantras\MantraResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMantra extends ViewRecord
{
    protected static string $resource = MantraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
