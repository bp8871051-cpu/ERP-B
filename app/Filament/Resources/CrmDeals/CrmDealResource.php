<?php

namespace App\Filament\Resources\CrmDeals;

use App\Filament\Resources\CrmDeals\Pages\CreateCrmDeal;
use App\Filament\Resources\CrmDeals\Pages\EditCrmDeal;
use App\Filament\Resources\CrmDeals\Pages\ListCrmDeals;
use App\Filament\Resources\CrmDeals\Schemas\CrmDealForm;
use App\Filament\Resources\CrmDeals\Tables\CrmDealsTable;
use App\Models\CrmDeal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CrmDealResource extends Resource
{
    protected static ?string $model = CrmDeal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CrmDealForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmDealsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmDeals::route('/'),
            'create' => CreateCrmDeal::route('/create'),
            'edit' => EditCrmDeal::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
