<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuddhistVideos\Pages;

use App\Filament\Resources\BuddhistVideos\BuddhistVideoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBuddhistVideos extends ListRecords
{
    protected static string $resource = BuddhistVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
