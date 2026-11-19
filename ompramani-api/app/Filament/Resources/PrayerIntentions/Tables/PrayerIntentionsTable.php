<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrayerIntentions\Tables;

use App\Enums\PrayerCategory;
use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerRejectionReason;
use App\Enums\PrayerVisibility;
use App\Models\PrayerIntention;
use App\Services\Prayer\PrayerModerationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PrayerIntentionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('submitted_at')
                    ->label('Thời gian gửi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('body')
                    ->label('Nội dung tin nhắn / Lời nguyện')
                    ->limit(65)
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('user.name')
                    ->label('Người gửi (nội bộ)')
                    ->searchable()
                    ->placeholder('Ẩn danh'),

                TextColumn::make('visibility')
                    ->label('Chế độ')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PrayerVisibility ? $state->label() : (PrayerVisibility::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn ($state): string => match ($state instanceof PrayerVisibility ? $state->value : (string) $state) {
                        'public' => 'primary',
                        'private' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('moderation_status')
                    ->label('Kiểm duyệt')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PrayerModerationStatus ? $state->label() : (PrayerModerationStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn ($state): string => match ($state instanceof PrayerModerationStatus ? $state->value : (string) $state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('reports_count')
                    ->counts('reports')
                    ->label('Báo cáo')
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'gray'),

                TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label('Phản hồi')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('moderator.name')
                    ->label('Người duyệt')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('published_at')
                    ->label('Đã xuất bản')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('moderation_status')
                    ->label('Trạng thái kiểm duyệt')
                    ->options(collect(PrayerModerationStatus::cases())->mapWithKeys(
                        fn (PrayerModerationStatus $s): array => [$s->value => $s->label()]
                    )->all()),

                SelectFilter::make('visibility')
                    ->label('Chế độ hiển thị')
                    ->options(collect(PrayerVisibility::cases())->mapWithKeys(
                        fn (PrayerVisibility $v): array => [$v->value => $v->label()]
                    )->all()),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Xem chi tiết'),

                Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (PrayerIntention $record): bool => $record->moderation_status === PrayerModerationStatus::Pending && $record->visibility === PrayerVisibility::Public)
                    ->requiresConfirmation()
                    ->modalHeading('Duyệt lời nguyện công khai')
                    ->modalDescription('Lời nguyện này sẽ được công khai hiển thị trong Phòng Lời Nguyện. Bạn có chắc chắn muốn duyệt?')
                    ->action(function (PrayerIntention $record, PrayerModerationService $service): void {
                        $service->approve(auth()->user(), $record);
                        Notification::make()
                            ->title('Đã duyệt lời nguyện thành công')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (PrayerIntention $record): bool => $record->moderation_status === PrayerModerationStatus::Pending)
                    ->form([
                        Select::make('reason_code')
                            ->label('Lý do từ chối')
                            ->options(collect(PrayerRejectionReason::cases())->mapWithKeys(
                                fn (PrayerRejectionReason $r): array => [$r->value => $r->adminLabel()]
                            )->all())
                            ->default(PrayerRejectionReason::PersonalInformation->value)
                            ->required(),

                        Textarea::make('user_note')
                            ->label('Thông điệp giải thích gửi người dùng (tùy chọn)')
                            ->rows(3),

                        Textarea::make('admin_notes')
                            ->label('Ghi chú nội bộ ban quản trị')
                            ->rows(2),
                    ])
                    ->action(function (PrayerIntention $record, array $data, PrayerModerationService $service): void {
                        $service->reject(
                            auth()->user(),
                            $record,
                            $data['reason_code'],
                            $data['user_note'] ?? null,
                            $data['admin_notes'] ?? null
                        );
                        Notification::make()
                            ->title('Đã từ chối lời nguyện')
                            ->warning()
                            ->send();
                    }),

                Action::make('revoke')
                    ->label('Ẩn / Thu hồi')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->color('warning')
                    ->visible(fn (PrayerIntention $record): bool => $record->moderation_status === PrayerModerationStatus::Approved && $record->withdrawn_at === null)
                    ->requiresConfirmation()
                    ->modalHeading('Ẩn lời nguyện khỏi phòng cộng đồng')
                    ->modalDescription('Lời nguyện này sẽ lập tức biến mất khỏi phòng chat công khai. Bạn có chắc chắn muốn ẩn?')
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Lý do ẩn / thu hồi')
                            ->required(),
                    ])
                    ->action(function (PrayerIntention $record, array $data, PrayerModerationService $service): void {
                        $service->revoke(auth()->user(), $record, $data['admin_notes'] ?? null);
                        Notification::make()
                            ->title('Đã ẩn lời nguyện khỏi phòng cộng đồng')
                            ->info()
                            ->send();
                    }),

                Action::make('restore')
                    ->label('Khôi phục')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (PrayerIntention $record): bool => ($record->moderation_status === PrayerModerationStatus::Rejected || $record->withdrawn_at !== null) && ! $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('Khôi phục lời nguyện')
                    ->modalDescription('Đưa lời nguyện này trở lại hàng chờ duyệt để tái thẩm định.')
                    ->action(function (PrayerIntention $record): void {
                        $record->moderation_status = PrayerModerationStatus::Pending;
                        $record->withdrawn_at = null;
                        $record->rejected_at = null;
                        $record->save();

                        Notification::make()
                            ->title('Đã đưa lời nguyện về hàng chờ duyệt')
                            ->success()
                            ->send();
                    }),

                Action::make('view_reports')
                    ->label('Xem báo cáo')
                    ->icon('heroicon-o-flag')
                    ->color('danger')
                    ->visible(fn (PrayerIntention $record): bool => $record->reports()->count() > 0)
                    ->modalHeading('Lý do báo cáo vi phạm')
                    ->modalDescription(function (PrayerIntention $record): string {
                        $reports = $record->reports()->with('reporter')->get();
                        if ($reports->isEmpty()) {
                            return 'Không có báo cáo nào.';
                        }

                        return $reports->map(function ($r, $idx) {
                            $reason = $r->reason instanceof \App\Enums\PrayerResponseReportReason ? $r->reason->label() : (string) $r->reason;
                            $details = $r->details ? " - Chi tiết: {$r->details}" : '';
                            $time = $r->created_at ? $r->created_at->format('d/m/Y H:i') : '';

                            return ($idx + 1).". [{$time}] Lý do: {$reason}{$details}";
                        })->implode("\n\n");
                    }),

                DeleteAction::make(),
            ])
            ->defaultSort('submitted_at', 'desc');
    }
}
