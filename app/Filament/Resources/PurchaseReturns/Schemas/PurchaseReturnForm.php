<?php

namespace App\Filament\Resources\PurchaseReturns\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PurchaseReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('return_number')
                    ->required()
                    ->maxLength(50),
                Select::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('purchase_id')
                    ->relationship('purchase', 'purchase_number')
                    ->searchable()
                    ->preload(),
                Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),
                DatePicker::make('return_date')
                    ->default(now())
                    ->required(),
                TextInput::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'requested' => 'Requested',
                        'approved' => 'Approved',
                        'returned' => 'Returned',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
                    ->required(),
                Textarea::make('reason')
                    ->columnSpanFull(),
            ]);
    }
}
