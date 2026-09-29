<?php

namespace App\Filament\Resources\CrmActivities\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CrmActivityForm
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
                TextInput::make('type')
                    ->required(),
                TextInput::make('subject')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('contact_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('lead_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('deal_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('customer_id')
                    ->numeric()
                    ->default(null),
                DateTimePicker::make('due_at'),
                DateTimePicker::make('completed_at'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
            ]);
    }
}
