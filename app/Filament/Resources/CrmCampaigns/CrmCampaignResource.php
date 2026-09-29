<?php

namespace App\Filament\Resources\CrmCampaigns;

use App\Filament\Resources\CrmCampaigns\Pages\CreateCrmCampaign;
use App\Filament\Resources\CrmCampaigns\Pages\EditCrmCampaign;
use App\Filament\Resources\CrmCampaigns\Pages\ListCrmCampaigns;
use App\Filament\Resources\CrmCampaigns\Schemas\CrmCampaignForm;
use App\Filament\Resources\CrmCampaigns\Tables\CrmCampaignsTable;
use App\Models\CrmCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CrmCampaignResource extends Resource
{
    protected static ?string $model = CrmCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CrmCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmCampaignsTable::configure($table);
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
            'index' => ListCrmCampaigns::route('/'),
            'create' => CreateCrmCampaign::route('/create'),
            'edit' => EditCrmCampaign::route('/{record}/edit'),
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
