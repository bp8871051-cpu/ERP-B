<?php

namespace App\Filament\Resources\CrmLeads\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CrmLeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('contact_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('customer_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('name')
                    ->required(),
                TextInput::make('company_name')
                    ->default(null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                TextInput::make('job_title')
                    ->default(null),
                TextInput::make('website')
                    ->url()
                    ->default(null),
                TextInput::make('lead_source_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('campaign_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('owner_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('industry')
                    ->default(null),
                TextInput::make('company_size')
                    ->default(null),
                TextInput::make('budget')
                    ->numeric()
                    ->default(null),
                TextInput::make('expected_value')
                    ->numeric()
                    ->default(null),
                DatePicker::make('expected_close_date'),
                TextInput::make('score')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('score_category')
                    ->required()
                    ->default('cold'),
                TextInput::make('status')
                    ->required()
                    ->default('new'),
                TextInput::make('lost_reason')
                    ->default(null),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('tags')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('converted_at'),
            ]);
    }
}
