<?php

namespace App\Filament\Resources\CreditNotes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CreditNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('credit_note_number')
                    ->required()
                    ->maxLength(50),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_number')
                    ->searchable()
                    ->preload(),
                DatePicker::make('credit_note_date')
                    ->default(now())
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                TextInput::make('tax')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                TextInput::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'applied' => 'Applied',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
                    ->required(),
                Textarea::make('reason')
                    ->columnSpanFull(),
            ]);
    }
}
