<?php

namespace App\Filament\Resources\Notes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('content')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('checklist')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('color')
                    ->required()
                    ->default('#ffffff'),
                Toggle::make('is_pinned')
                    ->required(),
                Toggle::make('is_archived')
                    ->required(),
                Toggle::make('is_favorite')
                    ->required(),
            ]);
    }
}
