<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuddhistVideos;

use App\Filament\Resources\BuddhistVideos\Pages\CreateBuddhistVideo;
use App\Filament\Resources\BuddhistVideos\Pages\EditBuddhistVideo;
use App\Filament\Resources\BuddhistVideos\Pages\ListBuddhistVideos;
use App\Filament\Resources\BuddhistVideos\Pages\ViewBuddhistVideo;
use App\Filament\Resources\BuddhistVideos\Schemas\BuddhistVideoForm;
use App\Filament\Resources\BuddhistVideos\Tables\BuddhistVideosTable;
use App\Models\BuddhistVideo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BuddhistVideoResource extends Resource
{
    protected static ?string $model = BuddhistVideo::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $modelLabel = 'Video Phật pháp';

    protected static ?string $pluralModelLabel = 'Thư viện Video Phật pháp';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?int $navigationSort = 25;

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
        return BuddhistVideoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BuddhistVideosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBuddhistVideos::route('/'),
            'create' => CreateBuddhistVideo::route('/create'),
            'view' => ViewBuddhistVideo::route('/{record}'),
            'edit' => EditBuddhistVideo::route('/{record}/edit'),
        ];
    }
}
