<?php

namespace App\Filament\Resources\SutraVersions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SutraVersionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('sutra.title')
                    ->label('Sutra'),
                TextEntry::make('name'),
                TextEntry::make('translator')
                    ->placeholder('-'),
                TextEntry::make('language'),
                TextEntry::make('publisher')
                    ->placeholder('-'),
                TextEntry::make('publication_year')
                    ->placeholder('-'),
                TextEntry::make('source')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_default')
                    ->boolean(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
