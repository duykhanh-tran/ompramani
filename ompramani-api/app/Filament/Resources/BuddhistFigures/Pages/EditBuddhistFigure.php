<?php

namespace App\Filament\Resources\BuddhistFigures\Pages;

use App\Filament\Resources\BuddhistFigures\BuddhistFigureResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBuddhistFigure extends EditRecord
{
    protected static string $resource = BuddhistFigureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
