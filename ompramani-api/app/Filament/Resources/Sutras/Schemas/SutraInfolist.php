<?php

namespace App\Filament\Resources\Sutras\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SutraInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('thumbnail')
                    ->label('Ảnh đại diện')
                    ->disk('public')
                    ->square(),
                TextEntry::make('title')
                    ->label('Tên kinh')
                    ->weight('bold'),
                TextEntry::make('tradition.name')
                    ->label('Tông phái / Truyền thống')
                    ->badge(),
                TextEntry::make('slug')
                    ->label('Slug'),
                TextEntry::make('translator')
                    ->label('Dịch giả')
                    ->placeholder('-'),
                TextEntry::make('language')
                    ->label('Ngôn ngữ')
                    ->badge(),
                TextEntry::make('publisher')
                    ->label('Nhà xuất bản')
                    ->placeholder('-'),
                TextEntry::make('publication_year')
                    ->label('Năm xuất bản')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label('Trạng thái')
                    ->badge(),
                TextEntry::make('published_at')
                    ->label('Thời điểm xuất bản')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('short_description')
                    ->label('Tóm tắt giới thiệu')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Thời điểm tạo')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Cập nhật lần cuối')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ]);
    }
}
