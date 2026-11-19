<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temples;

use App\Filament\Resources\Temples\Pages\CreateTemple;
use App\Filament\Resources\Temples\Pages\EditTemple;
use App\Filament\Resources\Temples\Pages\ListTemples;
use App\Filament\Resources\Temples\Pages\ViewTemple;
use App\Filament\Resources\Temples\Schemas\TempleForm;
use App\Filament\Resources\Temples\Tables\TemplesTable;
use App\Models\Temple;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TempleResource extends Resource
{
    protected static ?string $model = Temple::class;

    protected static string|UnitEnum|null $navigationGroup = 'Explore';

    protected static ?string $modelLabel = 'Tự viện & Đạo tràng';

    protected static ?string $pluralModelLabel = 'Thư viện Tự viện & Đạo tràng';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 15;

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
        return TempleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TemplesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemples::route('/'),
            'create' => CreateTemple::route('/create'),
            'view' => ViewTemple::route('/{record}'),
            'edit' => EditTemple::route('/{record}/edit'),
        ];
    }
}
