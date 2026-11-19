<?php

declare(strict_types=1);

namespace App\Filament\Resources\AlmanacMethods\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AlmanacMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Mã định danh (Key)')
                    ->required()
                    ->disabledOn('edit')
                    ->maxLength(100),

                TextInput::make('name')
                    ->label('Tên phương pháp lịch')
                    ->required()
                    ->maxLength(255),

                TextInput::make('version')
                    ->label('Phiên bản (Version)')
                    ->required()
                    ->maxLength(50)
                    ->default('1.0.0'),

                TextInput::make('source_title')
                    ->label('Tác phẩm / Nguồn tham chiếu')
                    ->required()
                    ->maxLength(255),

                TextInput::make('source_url')
                    ->label('Đường dẫn tham chiếu (URL)')
                    ->url()
                    ->maxLength(255),

                TextInput::make('license_details')
                    ->label('Bản quyền / Giấy phép')
                    ->maxLength(255)
                    ->default('Public Domain / Traditional Folk Heritage'),

                TextInput::make('calendar_timezone')
                    ->label('Múi giờ quy ước')
                    ->required()
                    ->default('Asia/Ho_Chi_Minh'),

                DatePicker::make('supported_from')
                    ->label('Ngày bắt đầu hỗ trợ')
                    ->required()
                    ->default('1900-01-31'),

                DatePicker::make('supported_to')
                    ->label('Ngày kết thúc hỗ trợ')
                    ->required()
                    ->default('2100-12-31'),

                Toggle::make('is_primary')
                    ->label('Phương pháp chính thức (Primary)')
                    ->default(true),

                Select::make('status')
                    ->label('Trạng thái hoạt động')
                    ->options([
                        'active' => 'Đang hoạt động (Active)',
                        'inactive' => 'Tạm ngưng (Inactive)',
                    ])
                    ->default('active')
                    ->required(),

                Textarea::make('description')
                    ->label('Mô tả nguyên lý phương pháp')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
