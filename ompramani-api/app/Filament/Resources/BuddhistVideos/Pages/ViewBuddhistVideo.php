<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuddhistVideos\Pages;

use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoEditorialStatus;
use App\Enums\VideoEmbedPermission;
use App\Enums\VideoProvider;
use App\Filament\Resources\BuddhistVideos\BuddhistVideoResource;
use App\Models\BuddhistVideo;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewBuddhistVideo extends ViewRecord
{
    protected static string $resource = BuddhistVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('publish')
                ->label('Xuất bản')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (BuddhistVideo $record) => $record->editorial_status !== VideoEditorialStatus::Published)
                ->requiresConfirmation()
                ->action(function (BuddhistVideo $record): void {
                    $record->editorial_status = VideoEditorialStatus::Published;
                    $record->published_at = $record->published_at ?? now();
                    $record->reviewed_by = auth()->id();
                    $record->reviewed_at = now();
                    if (in_array($record->provider, [VideoProvider::YouTube, VideoProvider::Vimeo], true) && filled($record->provider_video_id)) {
                        $record->embed_permission = VideoEmbedPermission::Approved;
                        $record->copyright_status = VideoCopyrightStatus::VerifiedEmbeddable;
                    }
                    $record->save();
                }),
            Action::make('unpublish')
                ->label('Thu hồi')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('warning')
                ->visible(fn (BuddhistVideo $record) => $record->editorial_status === VideoEditorialStatus::Published)
                ->requiresConfirmation()
                ->action(function (BuddhistVideo $record): void {
                    $record->editorial_status = VideoEditorialStatus::Draft;
                    $record->save();
                }),
        ];
    }
}
