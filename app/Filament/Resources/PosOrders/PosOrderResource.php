<?php

namespace App\Filament\Resources\PosOrders;

use App\Filament\Resources\PosOrders\Pages\CreatePosOrder;
use App\Filament\Resources\PosOrders\Pages\EditPosOrder;
use App\Filament\Resources\PosOrders\Pages\ListPosOrders;
use App\Filament\Resources\PosOrders\Schemas\PosOrderForm;
use App\Filament\Resources\PosOrders\Tables\PosOrdersTable;
use App\Models\PosOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PosOrderResource extends Resource
{
    protected static ?string $model = PosOrder::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public static function form(Schema $schema): Schema
    {
        return PosOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosOrders::route('/'),
            'create' => CreatePosOrder::route('/create'),
            'edit' => EditPosOrder::route('/{record}/edit'),
        ];
    }
}
