<?php

declare(strict_types=1);

namespace App\Filament\Resources\CalendarEvents\Tables;

use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use App\Services\Calendar\FixedCalendarEventRegistry;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CalendarEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Tên ngày lễ / Sự kiện')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(45),

                TextColumn::make('slug')
                    ->label('Mã sự kiện (Key)')
                    ->searchable()
                    ->fontFamily('mono')
                    ->copyable()
                    ->color('gray'),

                TextColumn::make('event_type')
                    ->label('Phân loại')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof CalendarEventType ? $state->label() : (CalendarEventType::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn ($state): string => match ($state instanceof CalendarEventType ? $state->value : (string) $state) {
                        'buddhist_holiday' => 'warning',
                        'buddha_bodhisattva_observance' => 'primary',
                        'vegetarian_day' => 'success',
                        'uposatha_or_observance' => 'info',
                        'practice_day' => 'gray',
                        'national_holiday' => 'danger',
                        'cultural_festival' => 'info',
                        'solar_term' => 'gray',
                        default => 'secondary',
                    }),

                TextColumn::make('rule_summary')
                    ->label('Quy tắc ngày tháng')
                    ->getStateUsing(function (CalendarEvent $record): string {
                        $fixed = FixedCalendarEventRegistry::get((string) $record->slug);

                        return $fixed['rule_summary'] ?? (is_object($record->recurrence_type) ? $record->recurrence_type->label() : (string) $record->recurrence_type);
                    })
                    ->limit(50),

                TextColumn::make('has_description')
                    ->label('Trạng thái mô tả')
                    ->badge()
                    ->getStateUsing(fn (CalendarEvent $record): string => filled($record->description) ? 'Đã có mô tả' : 'Chưa có mô tả')
                    ->color(fn (string $state): string => $state === 'Đã có mô tả' ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Loại sự kiện')
                    ->options(collect(CalendarEventType::cases())->mapWithKeys(
                        fn (CalendarEventType $type): array => [$type->value => $type->label()]
                    )->all()),

                SelectFilter::make('has_description')
                    ->label('Trạng thái mô tả')
                    ->options([
                        'with' => 'Đã có mô tả',
                        'without' => 'Chưa có mô tả',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['value'] ?? null) === 'with') {
                            return $query->whereNotNull('description')->where('description', '!=', '');
                        }
                        if (($data['value'] ?? null) === 'without') {
                            return $query->where(function (Builder $q): void {
                                $q->whereNull('description')->orWhere('description', '');
                            });
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('Xem chi tiết'),
                EditAction::make()->label('Chỉnh sửa mô tả'),
            ])
            ->toolbarActions([]);
    }
}
