<?php

namespace App\Filament\Resources\Sutras;

use App\Filament\Resources\Sutras\Pages\CreateSutra;
use App\Filament\Resources\Sutras\Pages\EditSutra;
use App\Filament\Resources\Sutras\Pages\ListSutras;
use App\Filament\Resources\Sutras\Pages\ViewSutra;
use App\Filament\Resources\Sutras\Schemas\SutraForm;
use App\Filament\Resources\Sutras\Schemas\SutraInfolist;
use App\Filament\Resources\Sutras\Tables\SutrasTable;
use App\Models\Sutra;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SutraResource extends Resource
{
    protected static ?string $model = Sutra::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SutraForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SutraInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SutrasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSutras::route('/'),
            'create' => CreateSutra::route('/create'),
            'view' => ViewSutra::route('/{record}'),
            'edit' => EditSutra::route('/{record}/edit'),
        ];
    }
}
