<?php

namespace App\Filament\Resources\Traditions;

use App\Filament\Resources\Traditions\Pages\CreateTradition;
use App\Filament\Resources\Traditions\Pages\EditTradition;
use App\Filament\Resources\Traditions\Pages\ListTraditions;
use App\Filament\Resources\Traditions\Pages\ViewTradition;
use App\Filament\Resources\Traditions\Schemas\TraditionForm;
use App\Filament\Resources\Traditions\Schemas\TraditionInfolist;
use App\Filament\Resources\Traditions\Tables\TraditionsTable;
use App\Models\Tradition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TraditionResource extends Resource
{
    protected static ?string $model = Tradition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TraditionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TraditionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TraditionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTraditions::route('/'),
            'create' => CreateTradition::route('/create'),
            'view' => ViewTradition::route('/{record}'),
            'edit' => EditTradition::route('/{record}/edit'),
        ];
    }
}
