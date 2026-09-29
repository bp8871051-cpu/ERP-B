<?php

namespace App\Filament\Resources\CrmPipelines;

use App\Filament\Resources\CrmPipelines\Pages\CreateCrmPipeline;
use App\Filament\Resources\CrmPipelines\Pages\EditCrmPipeline;
use App\Filament\Resources\CrmPipelines\Pages\ListCrmPipelines;
use App\Filament\Resources\CrmPipelines\Schemas\CrmPipelineForm;
use App\Filament\Resources\CrmPipelines\Tables\CrmPipelinesTable;
use App\Models\CrmPipeline;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CrmPipelineResource extends Resource
{
    protected static ?string $model = CrmPipeline::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CrmPipelineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmPipelinesTable::configure($table);
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
            'index' => ListCrmPipelines::route('/'),
            'create' => CreateCrmPipeline::route('/create'),
            'edit' => EditCrmPipeline::route('/{record}/edit'),
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
