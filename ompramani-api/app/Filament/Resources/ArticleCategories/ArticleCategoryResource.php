<?php

namespace App\Filament\Resources\ArticleCategories;

use App\Filament\Resources\ArticleCategories\Pages\CreateArticleCategory;
use App\Filament\Resources\ArticleCategories\Pages\EditArticleCategory;
use App\Filament\Resources\ArticleCategories\Pages\ListArticleCategories;
use App\Filament\Resources\ArticleCategories\Schemas\ArticleCategoryForm;
use App\Filament\Resources\ArticleCategories\Tables\ArticleCategoriesTable;
use App\Models\ArticleCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ArticleCategoryResource extends Resource
{
    protected static ?string $model = ArticleCategory::class;

    protected static string|UnitEnum|null $navigationGroup = 'Explore';

    protected static ?string $modelLabel = 'Danh mục bài viết';

    protected static ?string $pluralModelLabel = 'Danh mục bài viết';

    protected static ?string $navigationLabel = 'Danh mục bài viết';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = 1;

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
        return ArticleCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticleCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticleCategories::route('/'),
            'create' => CreateArticleCategory::route('/create'),
            'edit' => EditArticleCategory::route('/{record}/edit'),
        ];
    }
}
