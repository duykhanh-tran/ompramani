<?php

declare(strict_types=1);

namespace App\Filament\Resources\CalendarEvents;

use App\Filament\Resources\CalendarEvents\Pages\EditCalendarEvent;
use App\Filament\Resources\CalendarEvents\Pages\ListCalendarEvents;
use App\Filament\Resources\CalendarEvents\Pages\ViewCalendarEvent;
use App\Filament\Resources\CalendarEvents\Schemas\CalendarEventForm;
use App\Filament\Resources\CalendarEvents\Tables\CalendarEventsTable;
use App\Models\CalendarEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CalendarEventResource extends Resource
{
    protected static ?string $model = CalendarEvent::class;

    protected static string|UnitEnum|null $navigationGroup = 'Buddhist Content';

    protected static ?string $modelLabel = 'Lịch Sự Kiện';

    protected static ?string $pluralModelLabel = 'Lịch Sự Kiện';

    protected static ?string $navigationLabel = 'Lịch Sự Kiện';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return false; // Fixed Event Registry in code; no manual event creation
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Fixed events cannot be deleted
    }

    public static function form(Schema $schema): Schema
    {
        return CalendarEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CalendarEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalendarEvents::route('/'),
            'view' => ViewCalendarEvent::route('/{record}'),
            'edit' => EditCalendarEvent::route('/{record}/edit'),
        ];
    }
}
