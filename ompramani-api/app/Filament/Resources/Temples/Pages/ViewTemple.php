<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temples\Pages;

use App\Filament\Resources\Temples\TempleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTemple extends ViewRecord
{
    protected static string $resource = TempleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
