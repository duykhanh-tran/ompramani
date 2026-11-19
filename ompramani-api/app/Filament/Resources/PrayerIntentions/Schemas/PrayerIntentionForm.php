<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrayerIntentions\Schemas;

use App\Enums\PrayerCategory;
use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerRejectionReason;
use App\Enums\PrayerVisibility;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PrayerIntentionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('public_id')
                    ->label('Public Identifier (ULID)')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('title')
                    ->label('Tiêu đề tâm nguyện')
                    ->maxLength(255),

                Select::make('category')
                    ->label('Chủ đề')
                    ->options(collect(PrayerCategory::cases())->mapWithKeys(
                        fn (PrayerCategory $c): array => [$c->value => $c->label()]
                    )->all()),

                Select::make('visibility')
                    ->label('Chế độ hiển thị')
                    ->options(collect(PrayerVisibility::cases())->mapWithKeys(
                        fn (PrayerVisibility $v): array => [$v->value => $v->label()]
                    )->all())
                    ->required(),

                Select::make('moderation_status')
                    ->label('Trạng thái kiểm duyệt')
                    ->options(collect(PrayerModerationStatus::cases())->mapWithKeys(
                        fn (PrayerModerationStatus $s): array => [$s->value => $s->label()]
                    )->all())
                    ->required(),

                Textarea::make('body')
                    ->label('Nội dung lời nguyện')
                    ->rows(6)
                    ->required(),

                DateTimePicker::make('submitted_at')
                    ->label('Thời gian gửi chia sẻ'),

                DateTimePicker::make('approved_at')
                    ->label('Thời gian duyệt'),

                DateTimePicker::make('published_at')
                    ->label('Thời gian xuất bản'),

                DateTimePicker::make('withdrawn_at')
                    ->label('Thời gian rút lại / ngừng chia sẻ'),

                DateTimePicker::make('rejected_at')
                    ->label('Thời gian từ chối'),

                Select::make('rejection_reason_code')
                    ->label('Mã lý do từ chối')
                    ->options(collect(PrayerRejectionReason::cases())->mapWithKeys(
                        fn (PrayerRejectionReason $r): array => [$r->value => $r->adminLabel()]
                    )->all()),

                Textarea::make('user_rejection_note')
                    ->label('Thông điệp gửi người dùng (nếu từ chối)')
                    ->rows(3),

                Textarea::make('admin_notes')
                    ->label('Ghi chú nội bộ ban quản trị (không công khai)')
                    ->rows(3),
            ]);
    }
}
