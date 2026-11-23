<?php

namespace App\Filament\Resources\Sutras\Schemas;

use App\Models\Sutra;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SutraForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2])
            ->components([
                // Dòng 1: Tông phái | Tên Kinh
                Select::make('tradition_id')
                    ->label('Tông phái / Truyền thống')
                    ->relationship('tradition', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('title')
                    ->label('Tên kinh')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, $operation) {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),

                // Dòng 2: Slug | Dịch giả
                TextInput::make('slug')
                    ->label('Đường dẫn tĩnh (Slug)')
                    ->required()
                    ->maxLength(255)
                    ->unique(Sutra::class, 'slug', ignoreRecord: true),

                TextInput::make('translator')
                    ->label('Dịch giả')
                    ->maxLength(255),

                // Dòng 3: Ngôn ngữ | Nhà xuất bản
                Select::make('language')
                    ->label('Ngôn ngữ')
                    ->options([
                        'vi' => 'Tiếng Việt',
                        'zh' => 'Hán văn',
                        'pi' => 'Pali',
                        'sa' => 'Sanskrit',
                    ])
                    ->default('vi')
                    ->required(),

                TextInput::make('publisher')
                    ->label('Nhà xuất bản')
                    ->maxLength(255),

                // Dòng 4: Năm xuất bản | Trạng thái xuất bản
                TextInput::make('publication_year')
                    ->label('Năm xuất bản')
                    ->maxLength(30),

                Select::make('status')
                    ->label('Trạng thái xuất bản')
                    ->options([
                        'draft' => 'Bản nháp (Draft)',
                        'published' => 'Đã xuất bản (Published)',
                    ])
                    ->default('draft')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state === 'published' && ! $get('published_at')) {
                            $set('published_at', now()->toDateTimeString());
                        }
                    }),

                // Dòng 5: Thời điểm xuất bản | Ảnh đại diện
                DateTimePicker::make('published_at')
                    ->label('Thời điểm xuất bản')
                    ->nullable(),

                FileUpload::make('thumbnail')
                    ->label('Ảnh đại diện')
                    ->image()
                    ->disk((string) config('filesystems.public_disk', 'public'))
                    ->directory('sutras/thumbnails')
                    ->visibility('public')
                    ->fetchFileInformation(false)
                    ->maxSize(5120)
                    ->helperText('Tải ảnh đại diện từ máy tính (định dạng JPG, PNG, WEBP, tối đa 5MB).'),

                // Dòng 6: Tóm tắt giới thiệu (full width textarea)
                Textarea::make('short_description')
                    ->label('Tóm tắt giới thiệu')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
