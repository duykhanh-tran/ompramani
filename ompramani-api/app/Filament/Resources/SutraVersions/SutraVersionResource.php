<?php

namespace App\Filament\Resources\SutraVersions;

use App\Filament\Resources\SutraVersions\Pages\CreateSutraVersion;
use App\Filament\Resources\SutraVersions\Pages\EditSutraVersion;
use App\Filament\Resources\SutraVersions\Pages\ListSutraVersions;
use App\Filament\Resources\SutraVersions\Pages\ViewSutraVersion;
use App\Filament\Resources\SutraVersions\RelationManagers\AllSectionsRelationManager;
use App\Filament\Resources\SutraVersions\Schemas\SutraVersionForm;
use App\Filament\Resources\SutraVersions\Schemas\SutraVersionInfolist;
use App\Filament\Resources\SutraVersions\Tables\SutraVersionsTable;
use App\Models\SutraVersion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SutraVersionResource extends Resource
{
    protected static ?string $model = SutraVersion::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Phiên bản kinh';

    protected static ?string $pluralModelLabel = 'Các phiên bản kinh';

    protected static ?string $navigationLabel = 'Phiên bản kinh';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SutraVersionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SutraVersionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SutraVersionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AllSectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSutraVersions::route('/'),
            'create' => CreateSutraVersion::route('/create'),
            'view' => ViewSutraVersion::route('/{record}'),
            'edit' => EditSutraVersion::route('/{record}/edit'),
        ];
    }
}
