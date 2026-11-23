<?php

namespace App\Filament\Resources\BuddhistFigures\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BuddhistFigureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // NHÓM A: THÔNG TIN CƠ BẢN
                Section::make('Thông tin cơ bản')
                    ->description('Các thông tin cốt lõi hiển thị trên hệ thống và liên kết tu tập.')
                    ->schema([
                        // 1. Danh hiệu
                        TextInput::make('name')
                            ->label('Danh hiệu')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set, callable $get): void {
                                if ($operation === 'create' && filled($state) && blank($get('slug'))) {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->columnSpan(['default' => 1, 'md' => 1]),

                        // 2. Đường dẫn (Slug)
                        TextInput::make('slug')
                            ->label('Đường dẫn (Slug)')
                            ->required()
                            ->unique('buddhist_figures', 'slug', ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Đường dẫn tĩnh cho website (ví dụ: quan-the-am-bo-tat).')
                            ->columnSpan(['default' => 1, 'md' => 1]),

                        // 3. Danh hiệu trì niệm
                        TextInput::make('invocation_text')
                            ->label('Danh hiệu trì niệm')
                            ->helperText('Danh hiệu chuẩn dùng cho hành trì niệm Phật (Ví dụ: Nam mô A Di Đà Phật). Để trống nếu chưa xác minh.')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 1]),

                        // 4. Phân loại
                        Select::make('figure_type')
                            ->label('Phân loại')
                            ->options([
                                'buddha' => 'Đức Phật',
                                'bodhisattva' => 'Bồ Tát',
                                'arahant' => 'A La Hán',
                                'teacher' => 'Tổ Sư / Thiền Sư',
                                'other' => 'Khác',
                            ])
                            ->default('bodhisattva')
                            ->required()
                            ->columnSpan(['default' => 1, 'md' => 1]),

                        // 5. Truyền thống / Tông phái
                        Select::make('tradition_id')
                            ->label('Truyền thống / Tông phái')
                            ->relationship('tradition', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(['default' => 1, 'md' => 1]),

                        // 6. Trạng thái xuất bản
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'draft' => 'Bản nháp',
                                'review' => 'Chờ duyệt',
                                'published' => 'Đã xuất bản',
                            ])
                            ->default('draft')
                            ->required()
                            ->columnSpan(['default' => 1, 'md' => 1]),

                        // 7. Ảnh đại diện (Upload từ máy tính)
                        FileUpload::make('thumbnail')
                            ->label('Ảnh đại diện')
                            ->image()
                            ->disk((string) config('filesystems.public_disk', 'public'))
                            ->directory('buddhist-figures')
                            ->visibility('public')
                            ->fetchFileInformation(false)
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                            ->helperText('Tải ảnh đại diện từ máy tính (định dạng JPG, PNG, WEBP, tối đa 5MB). Thần Chú liên kết sẽ tự động kế thừa ảnh này.')
                            ->nullable()
                            ->columnSpanFull(),

                        // 8. Kinh liên quan
                        Select::make('sutras')
                            ->label('Kinh liên quan')
                            ->relationship('sutras', 'title')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Chọn các bộ kinh liên quan đến vị Phật / Bồ Tát này.')
                            ->columnSpan(['default' => 1, 'md' => 1]),

                        // 9. Thần chú liên quan
                        Select::make('mantras')
                            ->label('Thần chú liên quan')
                            ->relationship('mantras', 'title')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Chọn các bài thần chú liên quan đến vị Phật / Bồ Tát này.')
                            ->columnSpan(['default' => 1, 'md' => 1]),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),

                // NHÓM B: GIỚI THIỆU CHI TIẾT
                Section::make('Thông tin giới thiệu chi tiết')
                    ->description('Một trình soạn thảo duy nhất cho tiểu sử, công hạnh, xuất xứ hoặc hành trạng.')
                    ->schema([
                        RichEditor::make('biography')
                            ->label('Thông tin giới thiệu chi tiết')
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                                ['h1', 'h2', 'h3'],
                                ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                                ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                                ['table', 'attachFiles'],
                                ['undo', 'redo'],
                            ])
                            ->fileAttachmentsDisk((string) config('filesystems.public_disk', 'public'))
                            ->fileAttachmentsDirectory('buddhist-figures/content')
                            ->fileAttachmentsMaxSize(5120)
                            ->helperText('Soạn thảo nội dung phong phú với định dạng tiêu đề (H1, H2, H3), in đậm, in nghiêng, gạch chân, danh sách và liên kết theo hệ typography Noto Serif / Be Vietnam Pro.')
                            ->columnSpanFull(),
                    ]),

                // NHÓM C: ĐẠI NGUYỆN
                Section::make('Đại nguyện')
                    ->description('Quản lý phần giới thiệu chung và chi tiết từng đại nguyện độ sinh.')
                    ->schema([
                        RichEditor::make('vows_introduction')
                            ->label('Giới thiệu chung về Đại nguyện')
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                ['h2', 'h3'],
                                ['blockquote', 'bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ])
                            ->helperText('Tổng quan, bối cảnh hoặc ý nghĩa các lời đại nguyện độ sinh. Để trống nếu chưa có.')
                            ->nullable()
                            ->columnSpanFull(),

                        Repeater::make('vows')
                            ->label('Chi tiết từng Đại nguyện')
                            ->relationship('vows')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Tiêu đề Đại nguyện')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ví dụ: Nguyện thứ nhất: Cứu khổ nơi ba đường ác')
                                    ->columnSpanFull(),
                                Textarea::make('content')
                                    ->label('Nội dung chi tiết Đại nguyện')
                                    ->required()
                                    ->rows(4)
                                    ->placeholder('Chi tiết văn bản lời nguyện độ sinh...')
                                    ->columnSpanFull(),
                            ])
                            ->orderColumn('sort_order')
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel('Thêm Đại nguyện')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
