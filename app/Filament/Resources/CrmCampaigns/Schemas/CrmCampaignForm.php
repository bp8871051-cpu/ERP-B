<?php

namespace App\Filament\Resources\CrmCampaigns\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CrmCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('email'),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
                TextInput::make('owner_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('budget')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                Textarea::make('target_audience')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('total_audience')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('sent_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('delivered_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('opened_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('clicked_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('converted_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('revenue')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('cost')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('$'),
                TextInput::make('roi')
                    ->required()
                    ->numeric()
                    ->default(0.0),
            ]);
    }
}
