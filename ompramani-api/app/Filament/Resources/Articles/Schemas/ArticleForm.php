<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2])
            ->components([
                // Dòng 1: Tiêu đề | Đường dẫn
                TextInput::make('title')
                    ->label('Tiêu đề bài viết')
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

                // Dòng 2: Danh mục | Tác giả
                Select::make('article_category_id')
                    ->label('Danh mục')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('author_id')
                    ->label('Tác giả')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->id())
                    ->nullable(),

                // Dòng 3: Ảnh bìa (Upload) | Trạng thái
                FileUpload::make('cover_image')
                    ->label('Ảnh bìa')
                    ->image()
                    ->disk((string) config('filesystems.public_disk', 'public'))
                    ->directory('articles/covers')
                    ->maxSize(5120)
                    ->imagePreviewHeight('180')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->helperText('Tải ảnh bìa từ máy tính (định dạng JPG, PNG, WEBP, tối đa 5MB).')
                    ->nullable(),

                Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'draft' => 'Bản nháp',
                        'published' => 'Đã xuất bản',
                        'review' => 'Chờ duyệt',
                    ])
                    ->default('draft')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                        if ($state === 'published' && ! $get('published_at')) {
                            $set('published_at', now()->toDateTimeString());
                        }
                    }),

                // Dòng 4: Thời gian xuất bản
                DateTimePicker::make('published_at')
                    ->label('Thời gian xuất bản')
                    ->nullable()
                    ->columnSpan(['default' => 1, 'md' => 1]),

                // Dòng 5: Nội dung bài viết (Full Width)
                RichEditor::make('content')
                    ->label('Nội dung bài viết')
                    ->fileAttachmentsDisk((string) config('filesystems.public_disk', 'public'))
                    ->fileAttachmentsDirectory('articles/content')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
