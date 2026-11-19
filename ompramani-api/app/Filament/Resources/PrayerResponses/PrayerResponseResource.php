<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrayerResponses;

use App\Filament\Resources\PrayerResponses\Pages\ListPrayerResponses;
use App\Filament\Resources\PrayerResponses\Pages\ViewPrayerResponse;
use App\Filament\Resources\PrayerResponses\Schemas\PrayerResponseForm;
use App\Filament\Resources\PrayerResponses\Tables\PrayerResponsesTable;
use App\Models\PrayerResponse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PrayerResponseResource extends Resource
{
    protected static ?string $model = PrayerResponse::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|UnitEnum|null $navigationGroup = 'Buddhist Content';

    protected static ?string $modelLabel = 'Lời nhắn đồng hành';

    protected static ?string $pluralModelLabel = 'Lời nhắn đồng hành';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?int $navigationSort = 11;

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return PrayerResponseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrayerResponsesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrayerResponses::route('/'),
            'view' => ViewPrayerResponse::route('/{record}'),
        ];
    }
}
