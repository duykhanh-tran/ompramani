<?php

namespace App\Filament\Resources\BuddhistFigures;

use App\Filament\RelationManagers\RecitationAudiosRelationManager;
use App\Filament\Resources\BuddhistFigures\Pages\CreateBuddhistFigure;
use App\Filament\Resources\BuddhistFigures\Pages\EditBuddhistFigure;
use App\Filament\Resources\BuddhistFigures\Pages\ListBuddhistFigures;
use App\Filament\Resources\BuddhistFigures\Pages\ViewBuddhistFigure;
use App\Filament\Resources\BuddhistFigures\Schemas\BuddhistFigureForm;
use App\Filament\Resources\BuddhistFigures\Tables\BuddhistFiguresTable;
use App\Models\BuddhistFigure;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BuddhistFigureResource extends Resource
{
    protected static ?string $model = BuddhistFigure::class;

    protected static string|UnitEnum|null $navigationGroup = 'Buddhist Content';

    protected static ?string $modelLabel = 'Phật & Bồ Tát';

    protected static ?string $pluralModelLabel = 'Phật & Bồ Tát';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 3;

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
        return BuddhistFigureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BuddhistFiguresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RecitationAudiosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBuddhistFigures::route('/'),
            'create' => CreateBuddhistFigure::route('/create'),
            'view' => ViewBuddhistFigure::route('/{record}'),
            'edit' => EditBuddhistFigure::route('/{record}/edit'),
        ];
    }
}
