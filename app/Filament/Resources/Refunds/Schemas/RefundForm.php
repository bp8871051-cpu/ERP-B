<?php

namespace App\Filament\Resources\Refunds\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RefundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('refund_number')
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
                DatePicker::make('refund_date')
                    ->default(now())
                    ->required(),
                TextInput::make('refund_amount')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Select::make('refund_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'credit_card' => 'Credit Card',
                        'other' => 'Other',
                    ])
                    ->default('cash'),
                Select::make('status')
                    ->options([
                        'requested' => 'Requested',
                        'approved' => 'Approved',
                        'processed' => 'Processed',
                        'rejected' => 'Rejected',
                    ])
                    ->default('requested')
                    ->required(),
                Textarea::make('reason')
                    ->columnSpanFull(),
            ]);
    }
}
