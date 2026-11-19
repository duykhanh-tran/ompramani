<?php

namespace App\Filament\Resources\ArticleTags;

use App\Filament\Resources\ArticleTags\Pages\CreateArticleTag;
use App\Filament\Resources\ArticleTags\Pages\EditArticleTag;
use App\Filament\Resources\ArticleTags\Pages\ListArticleTags;
use App\Filament\Resources\ArticleTags\Schemas\ArticleTagForm;
use App\Filament\Resources\ArticleTags\Tables\ArticleTagsTable;
use App\Models\ArticleTag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ArticleTagResource extends Resource
{
    protected static ?string $model = ArticleTag::class;

    protected static string|UnitEnum|null $navigationGroup = 'Explore';

    protected static ?string $modelLabel = 'Thẻ bài viết';

    protected static ?string $pluralModelLabel = 'Thẻ bài viết';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ArticleTagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticleTagsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticleTags::route('/'),
            'create' => CreateArticleTag::route('/create'),
            'edit' => EditArticleTag::route('/{record}/edit'),
        ];
    }
}
