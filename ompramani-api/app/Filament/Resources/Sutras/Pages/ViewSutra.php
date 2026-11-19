<?php

namespace App\Filament\Resources\Sutras\Pages;

use App\Filament\Resources\Sutras\SutraResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSutra extends ViewRecord
{
    protected static string $resource = SutraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
