<?php

namespace App\Filament\Resources\CrmDeals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CrmDealForm
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
                TextInput::make('contact_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('lead_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('pipeline_id')
                    ->required()
                    ->numeric(),
                TextInput::make('stage_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('currency')
                    ->required()
                    ->default('INR'),
                TextInput::make('probability')
                    ->required()
                    ->numeric()
                    ->default(10),
                TextInput::make('expected_revenue')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                DatePicker::make('expected_close_date'),
                TextInput::make('owner_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                TextInput::make('lost_reason')
                    ->default(null),
                DateTimePicker::make('won_at'),
                DateTimePicker::make('lost_at'),
                TextInput::make('sales_order_id')
                    ->numeric()
                    ->default(null),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
