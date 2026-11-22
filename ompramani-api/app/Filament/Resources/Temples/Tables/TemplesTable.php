<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temples\Tables;

use App\Enums\TemplePublicationStatus;
use App\Enums\TempleType;
use App\Enums\TempleVerificationStatus;
use App\Models\Temple;
use App\Services\Media\MediaUrlResolver;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TemplesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Ảnh')
                    ->visibility('public')
                    ->checkFileExistence(false)
                    ->disk((string) config('filesystems.public_disk', 'public'))
                    ->state(fn ($record): ?string => MediaUrlResolver::resolve($record->getCoverImageUrl()))
                    ->square()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Tên tự viện')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                TextColumn::make('place_type')
                    ->label('Phân loại')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TempleType ? $state->label() : (TempleType::tryFrom((string) $state)?->label() ?? $state)),

                TextColumn::make('province_name')
                    ->label('Tỉnh / Thành')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('verification_status')
                    ->label('Xác minh')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TempleVerificationStatus ? $state->label() : (TempleVerificationStatus::tryFrom((string) $state)?->label() ?? $state))
                    ->color(fn ($state) => match ($state instanceof TempleVerificationStatus ? $state->value : $state) {
                        'verified' => 'success',
                        'pending_review' => 'warning',
                        'disputed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('publication_status')
                    ->label('Xuất bản')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TemplePublicationStatus ? $state->label() : (TemplePublicationStatus::tryFrom((string) $state)?->label() ?? $state))
                    ->color(fn ($state) => match ($state instanceof TemplePublicationStatus ? $state->value : $state) {
                        'published' => 'success',
                        'in_review' => 'warning',
                        'archived' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('published_at')
                    ->label('Ngày xuất bản')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('place_type')
                    ->label('Phân loại cơ sở')
                    ->options(collect(TempleType::cases())->mapWithKeys(
                        fn (TempleType $t): array => [$t->value => $t->label()]
                    )->all()),

                SelectFilter::make('verification_status')
                    ->label('Trạng thái xác minh')
                    ->options(collect(TempleVerificationStatus::cases())->mapWithKeys(
                        fn (TempleVerificationStatus $v): array => [$v->value => $v->label()]
                    )->all()),

                SelectFilter::make('publication_status')
                    ->label('Trạng thái xuất bản')
                    ->options(collect(TemplePublicationStatus::cases())->mapWithKeys(
                        fn (TemplePublicationStatus $p): array => [$p->value => $p->label()]
                    )->all()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('verify')
                    ->label('Xác minh')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Temple $record): bool => $record->verification_status !== TempleVerificationStatus::Verified)
                    ->action(function (Temple $record): void {
                        $record->update([
                            'verification_status' => TempleVerificationStatus::Verified,
                            'last_verified_at' => now(),
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    }),

                Action::make('publish')
                    ->label('Xuất bản')
                    ->icon(Heroicon::OutlinedArrowUpCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Temple $record): bool => $record->publication_status !== TemplePublicationStatus::Published)
                    ->action(function (Temple $record): void {
                        $record->update([
                            'publication_status' => TemplePublicationStatus::Published,
                            'published_at' => $record->published_at ?? now(),
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    }),

                Action::make('unpublish')
                    ->label('Thu hồi (Draft)')
                    ->icon(Heroicon::OutlinedArrowDownCircle)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Temple $record): bool => $record->publication_status === TemplePublicationStatus::Published)
                    ->action(function (Temple $record): void {
                        $record->update([
                            'publication_status' => TemplePublicationStatus::Draft,
                        ]);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
