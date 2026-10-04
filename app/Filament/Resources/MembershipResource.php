<?php

namespace App\Filament\Resources;

use App\Models\Membership;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MembershipResource extends Resource
{
    protected static ?string $model = Membership::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';
    protected static string|UnitEnum|null $navigationGroup = 'Membership Management';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')
                ->relationship('customer', 'name')
                ->searchable()
                ->required(),
            Select::make('plan_id')
                ->relationship('plan', 'name')
                ->required(),
            Select::make('billing_cycle')
                ->options([
                    'monthly' => 'Monthly',
                    'quarterly' => 'Quarterly',
                    'half_yearly' => 'Half-Yearly',
                    'yearly' => 'Yearly',
                    'lifetime' => 'Lifetime',
                ])->required(),
            DatePicker::make('starts_at')->required(),
            DatePicker::make('expires_at'),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'expiring' => 'Expiring Soon',
                    'expired' => 'Expired',
                    'cancelled' => 'Cancelled',
                ])->default('active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')->searchable()->sortable(),
                TextColumn::make('plan.name')->sortable(),
                TextColumn::make('billing_cycle')->badge(),
                TextColumn::make('starts_at')->date()->sortable(),
                TextColumn::make('expires_at')->date()->sortable(),
                TextColumn::make('status')->badge(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => MembershipResource\Pages\ListMemberships::route('/'),
        ];
    }
}
