<?php

namespace App\Filament\Resources\EmailAccounts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmailAccountForm
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
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('provider')
                    ->required()
                    ->default('smtp'),
                TextInput::make('incoming_host')
                    ->default(null),
                TextInput::make('incoming_port')
                    ->numeric()
                    ->default(null),
                TextInput::make('outgoing_host')
                    ->default(null),
                TextInput::make('outgoing_port')
                    ->numeric()
                    ->default(null),
                Textarea::make('credentials')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_default')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
