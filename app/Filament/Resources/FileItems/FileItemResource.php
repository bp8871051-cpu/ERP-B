<?php

namespace App\Filament\Resources\FileItems;

use App\Filament\Resources\FileItems\Pages\CreateFileItem;
use App\Filament\Resources\FileItems\Pages\EditFileItem;
use App\Filament\Resources\FileItems\Pages\ListFileItems;
use App\Filament\Resources\FileItems\Schemas\FileItemForm;
use App\Filament\Resources\FileItems\Tables\FileItemsTable;
use App\Models\FileItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FileItemResource extends Resource
{
    protected static ?string $model = FileItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Applications';

    protected static ?string $navigationLabel = 'Files';

    public static function form(Schema $schema): Schema
    {
        return FileItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FileItemsTable::configure($table);
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
            'index' => ListFileItems::route('/'),
            'create' => CreateFileItem::route('/create'),
            'edit' => EditFileItem::route('/{record}/edit'),
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
