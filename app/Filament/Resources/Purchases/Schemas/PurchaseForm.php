<?php

namespace App\Filament\Resources\Purchases\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('purchase_number')
                    ->required()
                    ->maxLength(50),
                Select::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('vendor_invoice_number')
                    ->maxLength(100),
                DatePicker::make('invoice_date')
                    ->default(now())
                    ->required(),
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
                    ])
                    ->default('unpaid'),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'received' => 'Received',
                        'partially_paid' => 'Partially Paid',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
                    ->required(),
            ]);
    }
}
