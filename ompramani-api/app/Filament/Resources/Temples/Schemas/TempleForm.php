<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temples\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TempleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2])
            ->components([
                // Dòng 1: Tên Tự Viện / Đạo Tràng | Tông phái
                TextInput::make('name')
                    ->label('Tên Tự Viện / Đạo Tràng')
                    ->required()
                    ->maxLength(255),

                Select::make('traditions')
                    ->label('Tông phái / Truyền thống')
                    ->relationship('traditions', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->nullable(),

                // Dòng 2: Năm xây dựng | Giờ mở cửa
                TextInput::make('construction_year')
                    ->label('Năm xây dựng')
                    ->maxLength(100)
                    ->placeholder('Ví dụ: 1010, Thế kỷ XI, Chưa rõ...')
                    ->nullable(),

                TextInput::make('opening_hours')
                    ->label('Giờ mở cửa')
                    ->maxLength(255)
                    ->placeholder('Ví dụ: 06:00–17:00')
                    ->nullable(),

                // Dòng 3: Tiền vé / Phí tham quan
                TextInput::make('admission_fee')
                    ->label('Tiền vé / Phí tham quan (VND)')
                    ->numeric()
                    ->minValue(0)
                    ->placeholder('Nhập 0 nếu miễn phí, để trống nếu chưa rõ thông tin')
                    ->helperText('Nhập số tiền vé VND (nhập 0 nếu xác nhận miễn phí vé, để trống nếu chưa có thông tin).')
                    ->suffix('VND')
                    ->nullable()
                    ->columnSpan(['default' => 1, 'md' => 1]),

                // Dòng 4: Địa chỉ (full width)
                TextInput::make('address_line')
                    ->label('Địa chỉ')
                    ->maxLength(500)
                    ->placeholder('Số nhà, tên đường, xã/phường, quận/huyện, tỉnh/thành phố...')
                    ->columnSpanFull()
                    ->nullable(),

                // Dòng 4b: Liên kết Google Maps (full width)
                TextInput::make('google_maps_url')
                    ->label('Liên kết Google Maps')
                    ->placeholder('Dán liên kết Google Maps của chùa')
                    ->helperText('Mở Google Maps, chọn đúng địa điểm, nhấn Chia sẻ và sao chép liên kết vào ô này.')
                    ->maxLength(2048)
                    ->rules([new \App\Rules\GoogleMapsUrl])
                    ->suffixAction(
                        \Filament\Actions\Action::make('open_test_link')
                            ->label('Mở thử')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->tooltip('Mở thử liên kết Google Maps')
                            ->url(fn (?string $state): ?string => \App\Rules\GoogleMapsUrl::isValid((string) $state) ? $state : null, shouldOpenInNewTab: true)
                            ->visible(fn (?string $state): bool => \App\Rules\GoogleMapsUrl::isValid((string) $state))
                    )
                    ->columnSpanFull()
                    ->nullable(),

                // Dòng 5: Ảnh (full width upload)
                FileUpload::make('cover_image')
                    ->label('Ảnh')
                    ->image()
                    ->disk((string) config('filesystems.public_disk', 'public'))
                    ->directory('temples/covers')
                    ->maxSize(5120)
                    ->imagePreviewHeight('200')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->helperText('Tải ảnh cơ sở từ máy tính (định dạng JPG, PNG, WEBP, tối đa 5MB).')
                    ->columnSpanFull()
                    ->nullable(),

                // Dòng 6: Giới thiệu (full width)
                Textarea::make('short_description')
                    ->label('Giới thiệu')
                    ->rows(3)
                    ->maxLength(1000)
                    ->helperText('Giới thiệu tổng quan ngắn gọn về tự viện hoặc đạo tràng.')
                    ->columnSpanFull()
                    ->nullable(),

                // Dòng 7: Điểm nổi bật (full width)
                Textarea::make('highlights')
                    ->label('Điểm nổi bật')
                    ->rows(3)
                    ->helperText('Đặc điểm kiến trúc, công trình tiêu biểu hoặc không gian tu học nổi bật.')
                    ->columnSpanFull()
                    ->nullable(),

                // Dòng 8: Lịch sử hình thành (full width)
                RichEditor::make('history')
                    ->label('Lịch sử hình thành')
                    ->fileAttachmentsDisk((string) config('filesystems.public_disk', 'public'))
                    ->fileAttachmentsDirectory('temples/content')
                    ->columnSpanFull()
                    ->nullable(),

                // Dòng 9: Kiến trúc – Không gian và Giá trị văn hóa (full width)
                RichEditor::make('architecture_culture')
                    ->label('Kiến trúc – Không gian và Giá trị văn hóa')
                    ->fileAttachmentsDisk((string) config('filesystems.public_disk', 'public'))
                    ->fileAttachmentsDirectory('temples/content')
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }
}
