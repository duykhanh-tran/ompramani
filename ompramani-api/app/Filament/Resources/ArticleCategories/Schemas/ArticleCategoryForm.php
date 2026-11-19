<?php

namespace App\Filament\Resources\ArticleCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArticleCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên danh mục')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Đường dẫn (Slug)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('position')
                    ->label('Thứ tự sắp xếp')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->label('Đang hoạt động')
                    ->default(true),

                Textarea::make('description')
                    ->label('Mô tả danh mục')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
