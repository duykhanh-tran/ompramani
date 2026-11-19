<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrayerResponses\Schemas;

use App\Enums\PrayerResponseModerationStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PrayerResponseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('public_id')
                    ->label('Mã công khai (ULID)')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('moderation_status')
                    ->label('Trạng thái kiểm duyệt')
                    ->options(collect(PrayerResponseModerationStatus::cases())->mapWithKeys(
                        fn (PrayerResponseModerationStatus $s): array => [$s->value => $s->label()]
                    )->all())
                    ->required(),

                TextInput::make('submission_revision')
                    ->label('Phiên bản sửa đổi')
                    ->disabled()
                    ->dehydrated(false),

                Textarea::make('body')
                    ->label('Nội dung lời nhắn')
                    ->rows(5)
                    ->required(),

                DateTimePicker::make('submitted_at')
                    ->label('Thời gian gửi'),

                DateTimePicker::make('approved_at')
                    ->label('Thời gian duyệt'),

                DateTimePicker::make('published_at')
                    ->label('Thời gian xuất bản'),

                DateTimePicker::make('withdrawn_at')
                    ->label('Thời gian rút lại'),

                DateTimePicker::make('rejected_at')
                    ->label('Thời gian từ chối'),

                DateTimePicker::make('revoked_at')
                    ->label('Thời gian thu hồi'),

                TextInput::make('rejection_reason_code')
                    ->label('Mã lý do từ chối / thu hồi'),

                Textarea::make('user_rejection_note')
                    ->label('Ghi chú gửi người dùng'),

                Textarea::make('admin_notes')
                    ->label('Ghi chú nội bộ Admin'),
            ]);
    }
}
