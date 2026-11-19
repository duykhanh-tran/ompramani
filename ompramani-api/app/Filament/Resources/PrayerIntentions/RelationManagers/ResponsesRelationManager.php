<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrayerIntentions\RelationManagers;

use App\Enums\PrayerResponseModerationStatus;
use App\Enums\PrayerResponseReportReason;
use App\Models\PrayerResponse;
use App\Services\Prayer\PrayerResponseModerationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Lời nhắn đồng hành & Phản hồi';

    protected static ?string $modelLabel = 'Lời nhắn';

    protected static ?string $pluralModelLabel = 'Lời nhắn đồng hành';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->label('Nội dung lời nhắn')
                    ->rows(4)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('submitted_at')
                    ->label('Gửi lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('body')
                    ->label('Nội dung')
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Người gửi (nội bộ)')
                    ->searchable(),

                TextColumn::make('moderation_status')
                    ->label('Kiểm duyệt')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PrayerResponseModerationStatus ? $state->label() : (PrayerResponseModerationStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn ($state): string => match ($state instanceof PrayerResponseModerationStatus ? $state->value : (string) $state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'revoked' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('reports_count')
                    ->counts('reports')
                    ->label('Báo cáo')
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'gray'),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PrayerResponse $record): bool => $record->moderation_status === PrayerResponseModerationStatus::Pending && $record->withdrawn_at === null)
                    ->requiresConfirmation()
                    ->modalHeading('Duyệt lời nhắn đồng hành')
                    ->modalDescription('Lời nhắn này sẽ xuất hiện công khai trong phòng lời nguyện.')
                    ->action(function (PrayerResponse $record): void {
                        try {
                            app(PrayerResponseModerationService::class)->approve(auth()->user(), $record, $record->submission_revision);
                            Notification::make()
                                ->title('Đã duyệt lời nhắn thành công')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Không thể duyệt lời nhắn')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PrayerResponse $record): bool => $record->moderation_status === PrayerResponseModerationStatus::Pending && $record->withdrawn_at === null)
                    ->form([
                        Select::make('reason_code')
                            ->label('Lý do từ chối')
                            ->options(collect(PrayerResponseReportReason::cases())->mapWithKeys(
                                fn (PrayerResponseReportReason $r): array => [$r->value => $r->label()]
                            )->all())
                            ->required(),

                        Textarea::make('user_note')
                            ->label('Ghi chú hướng dẫn người dùng')
                            ->maxLength(500)
                            ->rows(3),

                        Textarea::make('admin_notes')
                            ->label('Ghi chú nội bộ')
                            ->maxLength(500)
                            ->rows(2),
                    ])
                    ->action(function (PrayerResponse $record, array $data): void {
                        try {
                            app(PrayerResponseModerationService::class)->reject(
                                auth()->user(),
                                $record,
                                (string) $data['reason_code'],
                                $data['user_note'] ?? null,
                                $data['admin_notes'] ?? null
                            );
                            Notification::make()
                                ->title('Đã từ chối lời nhắn')
                                ->warning()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Lỗi thao tác')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('revoke')
                    ->label('Thu hồi / Ẩn')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (PrayerResponse $record): bool => $record->moderation_status === PrayerResponseModerationStatus::Approved)
                    ->form([
                        Select::make('reason_code')
                            ->label('Lý do thu hồi')
                            ->options(collect(PrayerResponseReportReason::cases())->mapWithKeys(
                                fn (PrayerResponseReportReason $r): array => [$r->value => $r->label()]
                            )->all())
                            ->required(),

                        Textarea::make('admin_notes')
                            ->label('Ghi chú kiểm duyệt')
                            ->rows(2),
                    ])
                    ->action(function (PrayerResponse $record, array $data): void {
                        try {
                            app(PrayerResponseModerationService::class)->revoke(
                                auth()->user(),
                                $record,
                                (string) $data['reason_code'],
                                $data['admin_notes'] ?? null
                            );
                            Notification::make()
                                ->title('Đã thu hồi lời nhắn')
                                ->info()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Lỗi thao tác')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                DeleteAction::make(),
            ]);
    }
}
