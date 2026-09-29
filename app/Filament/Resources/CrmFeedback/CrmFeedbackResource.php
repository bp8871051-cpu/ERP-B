<?php

namespace App\Filament\Resources\CrmFeedback;

use App\Filament\Resources\CrmFeedback\Pages\CreateCrmFeedback;
use App\Filament\Resources\CrmFeedback\Pages\EditCrmFeedback;
use App\Filament\Resources\CrmFeedback\Pages\ListCrmFeedback;
use App\Filament\Resources\CrmFeedback\Schemas\CrmFeedbackForm;
use App\Filament\Resources\CrmFeedback\Tables\CrmFeedbackTable;
use App\Models\CrmFeedback;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CrmFeedbackResource extends Resource
{
    protected static ?string $model = CrmFeedback::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CrmFeedbackForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmFeedbackTable::configure($table);
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
            'index' => ListCrmFeedback::route('/'),
            'create' => CreateCrmFeedback::route('/create'),
            'edit' => EditCrmFeedback::route('/{record}/edit'),
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
