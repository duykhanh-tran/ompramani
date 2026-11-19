<?php

namespace App\Filament\Resources\SutraVersions\Pages;

use App\Filament\Resources\SutraVersions\SutraVersionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSutraVersion extends ViewRecord
{
    protected static string $resource = SutraVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
