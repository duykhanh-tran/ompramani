<?php

declare(strict_types=1);

namespace App\Filament\Resources\AlmanacMethods;

use App\Filament\Resources\AlmanacMethods\Pages\EditAlmanacMethod;
use App\Filament\Resources\AlmanacMethods\Pages\ListAlmanacMethods;
use App\Filament\Resources\AlmanacMethods\Schemas\AlmanacMethodForm;
use App\Filament\Resources\AlmanacMethods\Tables\AlmanacMethodsTable;
use App\Models\AlmanacMethod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AlmanacMethodResource extends Resource
{
    protected static ?string $model = AlmanacMethod::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|UnitEnum|null $navigationGroup = 'Buddhist Content';

    protected static ?string $modelLabel = 'Phương pháp Lịch truyền thống';

    protected static ?string $pluralModelLabel = 'Phương pháp Lịch & Hoàng Đạo';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return false; // Created via seeder/developer code to ensure verified integrity
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AlmanacMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AlmanacMethodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlmanacMethods::route('/'),
            'edit' => EditAlmanacMethod::route('/{record}/edit'),
        ];
    }
}
