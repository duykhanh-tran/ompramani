<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuddhistVideos\Pages;

use App\Enums\VideoProvider;
use App\Filament\Resources\BuddhistVideos\BuddhistVideoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBuddhistVideo extends EditRecord
{
    protected static string $resource = BuddhistVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['canonical_source_url'])) {
            $provider = VideoProvider::detectFromUrl($data['canonical_source_url']);
            if ($provider) {
                $data['provider'] = $provider->value;
                $videoId = $provider->extractVideoId($data['canonical_source_url']);
                if ($videoId) {
                    $data['provider_video_id'] = $videoId;
                    if ($provider === VideoProvider::YouTube && empty($data['thumbnail_url'])) {
                        $data['thumbnail_url'] = "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";
                    }
                }
            }
        }

        return $data;
    }
}
