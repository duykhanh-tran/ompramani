<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrayerIntentions;

use App\Filament\Resources\PrayerIntentions\Pages\ListPrayerIntentions;
use App\Filament\Resources\PrayerIntentions\Pages\ViewPrayerIntention;
use App\Filament\Resources\PrayerIntentions\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\PrayerIntentions\Schemas\PrayerIntentionForm;
use App\Filament\Resources\PrayerIntentions\Tables\PrayerIntentionsTable;
use App\Models\PrayerIntention;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PrayerIntentionResource extends Resource
{
    protected static ?string $model = PrayerIntention::class;

    protected static string|UnitEnum|null $navigationGroup = 'Buddhist Content';

    protected static ?string $modelLabel = 'Lời nguyện';

    protected static ?string $pluralModelLabel = 'Phòng Lời Nguyện';

    protected static ?string $navigationLabel = 'Phòng Lời Nguyện';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 10;

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
        return PrayerIntentionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrayerIntentionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrayerIntentions::route('/'),
            'view' => ViewPrayerIntention::route('/{record}'),
        ];
    }
}
