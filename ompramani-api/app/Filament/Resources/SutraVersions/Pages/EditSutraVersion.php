<?php

namespace App\Filament\Resources\SutraVersions\Pages;

use App\Filament\Resources\SutraVersions\SutraVersionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSutraVersion extends EditRecord
{
    protected static string $resource = SutraVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
