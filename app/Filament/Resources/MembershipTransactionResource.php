<?php

namespace App\Filament\Resources;

use App\Models\MembershipTransaction;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MembershipTransactionResource extends Resource
{
    protected static ?string $model = MembershipTransaction::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|UnitEnum|null $navigationGroup = 'Membership Management';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('transaction_number')->required(),
            TextInput::make('amount')->numeric()->prefix('$')->required(),
            Select::make('payment_method')
                ->options([
                    'card' => 'Card',
                    'bank_transfer' => 'Bank Transfer',
                    'upi' => 'UPI',
                    'cash' => 'Cash',
                ])->required(),
            Select::make('status')
                ->options([
                    'paid' => 'Paid',
                    'pending' => 'Pending',
                    'failed' => 'Failed',
                    'refunded' => 'Refunded',
                ])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_number')->searchable()->sortable(),
                TextColumn::make('customer.name')->searchable(),
                TextColumn::make('amount')->money('USD')->sortable(),
                TextColumn::make('payment_method')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => MembershipTransactionResource\Pages\ListMembershipTransactions::route('/'),
        ];
    }
}
