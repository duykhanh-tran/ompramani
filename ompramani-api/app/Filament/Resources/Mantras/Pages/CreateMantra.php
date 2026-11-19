<?php

namespace App\Filament\Resources\Mantras\Pages;

use App\Filament\Resources\Mantras\MantraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMantra extends CreateRecord
{
    protected static string $resource = MantraResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }
}
