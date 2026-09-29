<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(50),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('invoice_date')
                    ->default(now())
                    ->required(),
                DatePicker::make('due_date')
                    ->default(now()->addDays(30)),
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
                TextInput::make('paid_amount')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                TextInput::make('due_amount')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                Select::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partially Paid',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                    ])
                    ->default('unpaid'),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft'),
            ]);
    }
}
