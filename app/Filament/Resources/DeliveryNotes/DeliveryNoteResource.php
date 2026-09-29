<?php

namespace App\Filament\Resources\DeliveryNotes;

use App\Filament\Resources\DeliveryNotes\Pages\CreateDeliveryNote;
use App\Filament\Resources\DeliveryNotes\Pages\EditDeliveryNote;
use App\Filament\Resources\DeliveryNotes\Pages\ListDeliveryNotes;
use App\Filament\Resources\DeliveryNotes\Schemas\DeliveryNoteForm;
use App\Filament\Resources\DeliveryNotes\Tables\DeliveryNotesTable;
use App\Models\DeliveryNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeliveryNoteResource extends Resource
{
    protected static ?string $model = DeliveryNote::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function form(Schema $schema): Schema
    {
        return DeliveryNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryNotesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryNotes::route('/'),
            'create' => CreateDeliveryNote::route('/create'),
            'edit' => EditDeliveryNote::route('/{record}/edit'),
        ];
    }
}
