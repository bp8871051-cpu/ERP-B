<?php

namespace App\Filament\Resources\CrmTasks;

use App\Filament\Resources\CrmTasks\Pages\CreateCrmTask;
use App\Filament\Resources\CrmTasks\Pages\EditCrmTask;
use App\Filament\Resources\CrmTasks\Pages\ListCrmTasks;
use App\Filament\Resources\CrmTasks\Schemas\CrmTaskForm;
use App\Filament\Resources\CrmTasks\Tables\CrmTasksTable;
use App\Models\CrmTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CrmTaskResource extends Resource
{
    protected static ?string $model = CrmTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CrmTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmTasksTable::configure($table);
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
            'index' => ListCrmTasks::route('/'),
            'create' => CreateCrmTask::route('/create'),
            'edit' => EditCrmTask::route('/{record}/edit'),
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
