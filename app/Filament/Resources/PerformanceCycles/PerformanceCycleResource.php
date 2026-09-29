<?php

namespace App\Filament\Resources\PerformanceCycles;

use App\Filament\Resources\PerformanceCycles\Pages\CreatePerformanceCycle;
use App\Filament\Resources\PerformanceCycles\Pages\EditPerformanceCycle;
use App\Filament\Resources\PerformanceCycles\Pages\ListPerformanceCycles;
use App\Filament\Resources\PerformanceCycles\Schemas\PerformanceCycleForm;
use App\Filament\Resources\PerformanceCycles\Tables\PerformanceCyclesTable;
use App\Models\PerformanceCycle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PerformanceCycleResource extends Resource
{
    protected static ?string $model = PerformanceCycle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PerformanceCycleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerformanceCyclesTable::configure($table);
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
            'index' => ListPerformanceCycles::route('/'),
            'create' => CreatePerformanceCycle::route('/create'),
            'edit' => EditPerformanceCycle::route('/{record}/edit'),
        ];
    }
}
