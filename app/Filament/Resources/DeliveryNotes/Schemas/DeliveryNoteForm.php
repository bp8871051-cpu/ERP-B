<?php

namespace App\Filament\Resources\DeliveryNotes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DeliveryNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('delivery_number')
                    ->required()
                    ->maxLength(50),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('sales_order_id')
                    ->relationship('salesOrder', 'order_number')
                    ->searchable()
                    ->preload(),
                Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),
                DatePicker::make('delivery_date')
                    ->default(now())
                    ->required(),
                TextInput::make('driver_name')
                    ->maxLength(100),
                TextInput::make('vehicle_number')
                    ->maxLength(50),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'packed' => 'Packed',
                        'dispatched' => 'Dispatched',
                        'partially_delivered' => 'Partially Delivered',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),
                Textarea::make('delivery_address')
                    ->columnSpanFull(),
            ]);
    }
}
