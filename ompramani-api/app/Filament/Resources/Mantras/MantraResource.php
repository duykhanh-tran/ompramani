<?php

namespace App\Filament\Resources\Mantras;

use App\Filament\Resources\Mantras\Pages\CreateMantra;
use App\Filament\Resources\Mantras\Pages\EditMantra;
use App\Filament\Resources\Mantras\Pages\ListMantras;
use App\Filament\Resources\Mantras\Pages\ViewMantra;
use App\Filament\Resources\Mantras\RelationManagers\MantraVersionsRelationManager;
use App\Filament\Resources\Mantras\Schemas\MantraForm;
use App\Filament\Resources\Mantras\Tables\MantrasTable;
use App\Models\Mantra;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MantraResource extends Resource
{
    protected static ?string $model = Mantra::class;

    protected static string|UnitEnum|null $navigationGroup = 'Buddhist Content';

    protected static ?string $modelLabel = 'Chú / Mantra';

    protected static ?string $pluralModelLabel = 'Thần Chú';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin';
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
        return MantraForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MantrasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMantras::route('/'),
            'create' => CreateMantra::route('/create'),
            'view' => ViewMantra::route('/{record}'),
            'edit' => EditMantra::route('/{record}/edit'),
        ];
    }
}
