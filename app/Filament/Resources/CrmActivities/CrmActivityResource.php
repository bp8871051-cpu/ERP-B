<?php

namespace App\Filament\Resources\CrmActivities;

use App\Filament\Resources\CrmActivities\Pages\CreateCrmActivity;
use App\Filament\Resources\CrmActivities\Pages\EditCrmActivity;
use App\Filament\Resources\CrmActivities\Pages\ListCrmActivities;
use App\Filament\Resources\CrmActivities\Schemas\CrmActivityForm;
use App\Filament\Resources\CrmActivities\Tables\CrmActivitiesTable;
use App\Models\CrmActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CrmActivityResource extends Resource
{
    protected static ?string $model = CrmActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CrmActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmActivitiesTable::configure($table);
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
            'index' => ListCrmActivities::route('/'),
            'create' => CreateCrmActivity::route('/create'),
            'edit' => EditCrmActivity::route('/{record}/edit'),
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
