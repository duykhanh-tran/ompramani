<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrayerIntentions\Pages;

use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerVisibility;
use App\Filament\Resources\PrayerIntentions\PrayerIntentionResource;
use App\Models\PrayerIntention;
use App\Models\PrayerIntentionReport;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListPrayerIntentions extends ListRecords
{
    protected static string $resource = PrayerIntentionResource::class;

    public function getTabs(): array
    {
        $pendingCount = PrayerIntention::pendingModeration()->count();
        $reportedCount = PrayerIntentionReport::pending()->distinct('prayer_intention_id')->count('prayer_intention_id');

        return [
            'pending' => Tab::make('Chờ duyệt')
                ->badge($pendingCount > 0 ? (string) $pendingCount : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn ($query) => $query->where('visibility', PrayerVisibility::Public->value)
                    ->where('moderation_status', PrayerModerationStatus::Pending->value)
                    ->whereNull('withdrawn_at')
                ),

            'approved' => Tab::make('Đã công khai')
                ->modifyQueryUsing(fn ($query) => $query->where('visibility', PrayerVisibility::Public->value)
                    ->where('moderation_status', PrayerModerationStatus::Approved->value)
                    ->whereNull('withdrawn_at')
                ),

            'reported' => Tab::make('Bị báo cáo')
                ->badge($reportedCount > 0 ? (string) $reportedCount : null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn ($query) => $query->has('reports')),

            'rejected' => Tab::make('Từ chối / Thu hồi')
                ->modifyQueryUsing(fn ($query) => $query->where(function ($q) {
                    $q->where('moderation_status', PrayerModerationStatus::Rejected->value)
                        ->orWhereNotNull('withdrawn_at');
                })),

            'all' => Tab::make('Tất cả'),
        ];
    }
}
