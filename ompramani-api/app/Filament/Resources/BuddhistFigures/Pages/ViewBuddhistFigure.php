<?php

namespace App\Filament\Resources\BuddhistFigures\Pages;

use App\Filament\Resources\BuddhistFigures\BuddhistFigureResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBuddhistFigure extends ViewRecord
{
    protected static string $resource = BuddhistFigureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
