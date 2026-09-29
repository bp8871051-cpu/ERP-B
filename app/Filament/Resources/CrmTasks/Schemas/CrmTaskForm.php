<?php

namespace App\Filament\Resources\CrmTasks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CrmTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('assigned_to')
                    ->numeric()
                    ->default(null),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
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
                TextInput::make('priority')
                    ->required()
                    ->default('medium'),
                DatePicker::make('due_date'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
            ]);
    }
}
