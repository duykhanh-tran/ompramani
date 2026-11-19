<?php

namespace App\Filament\Resources\Traditions\Pages;

use App\Filament\Resources\Traditions\TraditionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTradition extends ViewRecord
{
    protected static string $resource = TraditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
