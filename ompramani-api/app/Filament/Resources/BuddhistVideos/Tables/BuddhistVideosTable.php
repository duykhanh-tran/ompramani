<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuddhistVideos\Tables;

use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoEditorialStatus;
use App\Enums\VideoEmbedPermission;
use App\Enums\VideoProvider;
use App\Models\BuddhistVideo;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BuddhistVideosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Tiêu đề video')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),

                TextColumn::make('slug')
                    ->label('Đường dẫn (Slug)')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->limit(35),

                TextColumn::make('provider')
                    ->label('Nền tảng')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof VideoProvider ? $state->label() : (VideoProvider::tryFrom((string) $state)?->label() ?? $state)),

                TextColumn::make('canonical_source_url')
                    ->label('Liên kết video')
                    ->limit(40)
                    ->url(fn (BuddhistVideo $record) => $record->canonical_source_url, true),

                TextColumn::make('editorial_status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof VideoEditorialStatus ? $state->label() : (VideoEditorialStatus::tryFrom((string) $state)?->label() ?? $state))
                    ->color(fn ($state) => match ($state instanceof VideoEditorialStatus ? $state->value : $state) {
                        'published' => 'success',
                        'in_review' => 'warning',
                        'archived' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('published_at')
                    ->label('Ngày xuất bản')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->label('Nền tảng')
                    ->options(collect(VideoProvider::cases())->mapWithKeys(
                        fn (VideoProvider $p): array => [$p->value => $p->label()]
                    )->all()),

                SelectFilter::make('editorial_status')
                    ->label('Trạng thái xuất bản')
                    ->options(collect(VideoEditorialStatus::cases())->mapWithKeys(
                        fn (VideoEditorialStatus $e): array => [$e->value => $e->label()]
                    )->all()),
            ])
            ->actions([
                ViewAction::make(),
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
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
