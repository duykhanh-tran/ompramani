<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrayerResponses\Pages;

use App\Enums\PrayerResponseModerationStatus;
use App\Filament\Resources\PrayerResponses\PrayerResponseResource;
use App\Models\PrayerResponse;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListPrayerResponses extends ListRecords
{
    protected static string $resource = PrayerResponseResource::class;

    public function getTabs(): array
    {
        $pendingCount = PrayerResponse::where('moderation_status', PrayerResponseModerationStatus::Pending->value)
            ->whereNull('withdrawn_at')
            ->count();

        $reportedCount = PrayerResponse::whereHas('reports', fn ($q) => $q->where('status', 'pending'))->count();

        return [
            'pending' => Tab::make('Chờ duyệt')
                ->badge($pendingCount > 0 ? (string) $pendingCount : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn ($query) => $query->where('moderation_status', PrayerResponseModerationStatus::Pending->value)
                    ->whereNull('withdrawn_at')
                ),

            'approved' => Tab::make('Đã xuất bản')
                ->modifyQueryUsing(fn ($query) => $query->where('moderation_status', PrayerResponseModerationStatus::Approved->value)
                    ->whereNull('withdrawn_at')
                ),

            'rejected' => Tab::make('Chưa được chấp thuận')
                ->modifyQueryUsing(fn ($query) => $query->where('moderation_status', PrayerResponseModerationStatus::Rejected->value)),

            'revoked' => Tab::make('Đã thu hồi')
                ->modifyQueryUsing(fn ($query) => $query->where('moderation_status', PrayerResponseModerationStatus::Revoked->value)),

            'reported' => Tab::make('Bị báo cáo')
                ->badge($reportedCount > 0 ? (string) $reportedCount : null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn ($query) => $query->whereHas('reports')),

            'all' => Tab::make('Tất cả'),
        ];
    }
}
