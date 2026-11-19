<?php

namespace App\Filament\Resources\SutraVersions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SutraVersionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sutra.title')
                    ->label('Kinh')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Tên phiên bản')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('translator')
                    ->label('Dịch giả')
                    ->searchable(),

                TextColumn::make('language')
                    ->label('Ngôn ngữ')
                    ->searchable(),

                TextColumn::make('publisher')
                    ->label('Nhà xuất bản')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('publication_year')
                    ->label('Năm xuất bản')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_default')
                    ->label('Mặc định')
                    ->boolean(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
