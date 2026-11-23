<?php

namespace App\Filament\Resources\Mantras\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MantraForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'md' => 2])
            ->components([
                // Dòng 1: Tên Chú | Đường dẫn (Slug)
                TextInput::make('title')
                    ->label('Tên Chú / Mantra')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->columnSpan(['default' => 1, 'md' => 1]),

                TextInput::make('slug')
                    ->label('Đường dẫn (Slug)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpan(['default' => 1, 'md' => 1]),

                // Dòng 2: Phật / Bồ Tát liên kết
                Select::make('buddhist_figure_id')
                    ->label('Phật / Bồ Tát liên kết')
                    ->relationship('figure', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->placeholder('Chọn Đức Phật hoặc Bồ Tát liên kết (hình đại diện sẽ tự động kế thừa)...')
                    ->helperText('Hình ảnh đại diện của thần chú được tự động lấy từ Phật/Bồ Tát được chọn.')
                    ->columnSpanFull(),

                // Dòng 3: Mô tả ngắn (full width)
                Textarea::make('short_description')
                    ->label('Mô tả ngắn')
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Giới thiệu ngắn gọn về thần chú...')
                    ->columnSpanFull()
                    ->nullable(),

                // Dòng 4: Chú gốc (full width)
                Textarea::make('original_text')
                    ->label('Chú gốc')
                    ->rows(5)
                    ->placeholder('Văn bản tiếng Phạn (Sanskrit), Pali, Hán hoặc hệ chữ gốc...')
                    ->helperText('Văn bản chữ gốc của thần chú (hỗ trợ Unicode đầy đủ, xuống dòng và dấu câu).')
                    ->columnSpanFull()
                    ->nullable(),

                // Dòng 5: Phiên âm tiếng Việt (full width)
                Textarea::make('vietnamese_transcription')
                    ->label('Phiên âm tiếng Việt')
                    ->rows(5)
                    ->placeholder('Bản phiên âm cách đọc bằng tiếng Việt...')
                    ->helperText('Cách đọc phát âm bằng tiếng Việt do người quản trị cung cấp.')
                    ->columnSpanFull()
                    ->nullable(),

                // Dòng 6: Audio trì niệm (duy nhất 1 file audio)
                FileUpload::make('audio_path')
                    ->label('Audio trì niệm')
                    ->disk((string) config('filesystems.public_disk', 'public'))
                    ->directory('mantras/audio')
                    ->visibility('public')
                    ->fetchFileInformation(false)
                    ->maxSize(30720)
                    ->acceptedFileTypes([
                        'audio/mpeg',
                        'audio/mp3',
                        'audio/mp4',
                        'audio/x-m4a',
                        'audio/aac',
                        'audio/ogg',
                        'audio/wav',
                    ])
                    ->helperText('Tải lên một file âm thanh trì niệm (định dạng MP3, M4A, AAC, OGG; tối đa 30MB). Để trống nếu chưa có bản thu.')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
