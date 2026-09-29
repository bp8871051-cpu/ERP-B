<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('vendor_code')
                    ->required()
                    ->maxLength(50),
                TextInput::make('company_name')
                    ->maxLength(255),
                TextInput::make('contact_person')
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('city')
                    ->maxLength(100),
                TextInput::make('credit_limit')
                    ->numeric()
                    ->prefix('$'),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
                Textarea::make('address')
                    ->columnSpanFull(),
            ]);
    }
}
