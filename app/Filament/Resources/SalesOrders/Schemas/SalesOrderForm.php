<?php

namespace App\Filament\Resources\SalesOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->required()
                    ->maxLength(50),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),
                DatePicker::make('order_date')
                    ->default(now())
                    ->required(),
                DatePicker::make('expected_delivery_date'),
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
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'partially_delivered' => 'Partially Delivered',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->default('draft')
                    ->required(),
            ]);
    }
}
