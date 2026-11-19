<?php

declare(strict_types=1);

namespace App\Filament\Resources\CalendarEvents\Schemas;

use App\Services\Calendar\FixedCalendarEventRegistry;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CalendarEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin ngày lễ (Cố định trong mã nguồn)')
                    ->description('Tên sự kiện, mã định danh và quy tắc lịch được định nghĩa cố định trong hệ thống, không chỉnh sửa thủ công.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Tên ngày lễ / Sự kiện')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('slug')
                            ->label('Mã định danh (Event Key)')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('rule_summary')
                            ->label('Quy tắc ngày tháng & Hệ lịch')
                            ->afterStateHydrated(function ($component, $record): void {
                                if ($record) {
                                    $fixed = FixedCalendarEventRegistry::get((string) $record->slug);
                                    $component->state($fixed['rule_summary'] ?? (is_object($record->recurrence_type) ? $record->recurrence_type->label() : (string) $record->recurrence_type));
                                }
                            })
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Mô tả & Ý nghĩa ngày lễ')
                    ->description('Biên soạn nội dung ý nghĩa tâm linh, lịch sử và giá trị tu tập của ngày lễ hiển thị trên Lịch Vạn Niên.')
                    ->schema([
                        Textarea::make('short_description')
                            ->label('Tóm tắt ngắn (Trích dẫn)')
                            ->rows(2)
                            ->placeholder('Tóm tắt ngắn gọn 1-2 câu hiển thị trên thẻ sự kiện...')
                            ->helperText('Hiển thị dạng tóm lược khi xem nhanh trên lịch.'),

                        RichEditor::make('description')
                            ->label('Mô tả / Ý nghĩa chi tiết')
                            ->placeholder('Soạn thảo nội dung ý nghĩa, nguồn gốc, công hạnh và nghi thức kỷ niệm...')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'orderedList',
                                'bulletList',
                                'blockquote',
                                'h2',
                                'h3',
                                'undo',
                                'redo',
                            ])
                            ->helperText('Nội dung chi tiết được hiển thị khi người dùng bấm vào xem thông tin sự kiện.'),
                    ]),
            ]);
    }
}
