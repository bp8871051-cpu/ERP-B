<?php

namespace App\Filament\Resources\Conversations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ConversationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('type')
                    ->required()
                    ->default('direct'),
                TextInput::make('title')
                    ->default(null),
                TextInput::make('avatar')
                    ->default(null),
                TextInput::make('created_by')
                    ->numeric()
                    ->default(null),
                DateTimePicker::make('last_message_at'),
            ]);
    }
}
