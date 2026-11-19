<?php

namespace App\Filament\Resources\BuddhistFigures\Pages;

use App\Filament\Resources\BuddhistFigures\BuddhistFigureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBuddhistFigures extends ListRecords
{
    protected static string $resource = BuddhistFigureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
