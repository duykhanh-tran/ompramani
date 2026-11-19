<?php

namespace App\Filament\Resources\Mantras\Pages;

use App\Filament\Resources\Mantras\MantraResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMantra extends EditRecord
{
    protected static string $resource = MantraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
