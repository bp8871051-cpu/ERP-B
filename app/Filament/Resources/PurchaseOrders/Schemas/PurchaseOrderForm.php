<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('po_number')
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
                DatePicker::make('po_date')
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
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'ordered' => 'Ordered',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->default('draft')
                    ->required(),
            ]);
    }
}
