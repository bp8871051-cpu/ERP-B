<?php

namespace App\Filament\Resources\WorkflowRequests;

use App\Filament\Resources\WorkflowRequests\Pages\CreateWorkflowRequest;
use App\Filament\Resources\WorkflowRequests\Pages\EditWorkflowRequest;
use App\Filament\Resources\WorkflowRequests\Pages\ListWorkflowRequests;
use App\Filament\Resources\WorkflowRequests\Schemas\WorkflowRequestForm;
use App\Filament\Resources\WorkflowRequests\Tables\WorkflowRequestsTable;
use App\Models\WorkflowRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkflowRequestResource extends Resource
{
    protected static ?string $model = WorkflowRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Applications';

    public static function form(Schema $schema): Schema
    {
        return WorkflowRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkflowRequestsTable::configure($table);
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
            'index' => ListWorkflowRequests::route('/'),
            'create' => CreateWorkflowRequest::route('/create'),
            'edit' => EditWorkflowRequest::route('/{record}/edit'),
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
