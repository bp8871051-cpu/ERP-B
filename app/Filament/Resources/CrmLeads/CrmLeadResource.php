<?php

namespace App\Filament\Resources\CrmLeads;

use App\Filament\Resources\CrmLeads\Pages\CreateCrmLead;
use App\Filament\Resources\CrmLeads\Pages\EditCrmLead;
use App\Filament\Resources\CrmLeads\Pages\ListCrmLeads;
use App\Filament\Resources\CrmLeads\Schemas\CrmLeadForm;
use App\Filament\Resources\CrmLeads\Tables\CrmLeadsTable;
use App\Models\CrmLead;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CrmLeadResource extends Resource
{
    protected static ?string $model = CrmLead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CrmLeadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmLeadsTable::configure($table);
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
            'index' => ListCrmLeads::route('/'),
            'create' => CreateCrmLead::route('/create'),
            'edit' => EditCrmLead::route('/{record}/edit'),
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
