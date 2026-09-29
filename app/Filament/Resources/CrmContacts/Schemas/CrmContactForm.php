<?php

namespace App\Filament\Resources\CrmContacts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CrmContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('customer_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->default(null),
                TextInput::make('company_name')
                    ->default(null),
                TextInput::make('job_title')
                    ->default(null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->default(null),
                TextInput::make('secondary_email')
                    ->email()
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                TextInput::make('whatsapp')
                    ->default(null),
                TextInput::make('website')
                    ->url()
                    ->default(null),
                Textarea::make('address')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('city')
                    ->default(null),
                TextInput::make('state')
                    ->default(null),
                TextInput::make('country')
                    ->default('India'),
                TextInput::make('postal_code')
                    ->default(null),
                TextInput::make('contact_type')
                    ->required()
                    ->default('business'),
                TextInput::make('lead_source_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('owner_id')
                    ->numeric()
                    ->default(null),
                Textarea::make('tags')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                DateTimePicker::make('last_contacted_at'),
            ]);
    }
}
