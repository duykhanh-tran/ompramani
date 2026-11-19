<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temples\Pages;

use App\Filament\Resources\Temples\TempleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTemple extends EditRecord
{
    protected static string $resource = TempleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
