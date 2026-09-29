<?php

namespace App\Filament\Resources\SalesQuotes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SalesQuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('quote_number')
                    ->required()
                    ->maxLength(50),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('quote_date')
                    ->default(now())
                    ->required(),
                DatePicker::make('valid_until'),
                TextInput::make('subtotal')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                TextInput::make('tax')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                TextInput::make('grand_total')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                        'converted' => 'Converted',
                    ])
                    ->default('draft')
                    ->required(),
            ]);
    }
}
