<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuddhistVideos\Pages;

use App\Enums\VideoCategory;
use App\Enums\VideoEditorialStatus;
use App\Enums\VideoProvider;
use App\Filament\Resources\BuddhistVideos\BuddhistVideoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBuddhistVideo extends CreateRecord
{
    protected static string $resource = BuddhistVideoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['category'])) {
            $data['category'] = VideoCategory::Other->value;
        }

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

        $data['editorial_status'] = VideoEditorialStatus::Draft->value;

        return $data;
    }
}
