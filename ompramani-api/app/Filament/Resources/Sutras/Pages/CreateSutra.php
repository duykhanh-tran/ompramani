<?php

namespace App\Filament\Resources\Sutras\Pages;

use App\Filament\Resources\Sutras\SutraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSutra extends CreateRecord
{
    protected static string $resource = SutraResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
