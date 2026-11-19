<?php

namespace App\Filament\Resources\BuddhistFigures\Pages;

use App\Filament\Resources\BuddhistFigures\BuddhistFigureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBuddhistFigure extends CreateRecord
{
    protected static string $resource = BuddhistFigureResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }
}
