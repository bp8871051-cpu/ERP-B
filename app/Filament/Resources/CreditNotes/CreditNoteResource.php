<?php

namespace App\Filament\Resources\CreditNotes;

use App\Filament\Resources\CreditNotes\Pages\CreateCreditNote;
use App\Filament\Resources\CreditNotes\Pages\EditCreditNote;
use App\Filament\Resources\CreditNotes\Pages\ListCreditNotes;
use App\Filament\Resources\CreditNotes\Schemas\CreditNoteForm;
use App\Filament\Resources\CreditNotes\Tables\CreditNotesTable;
use App\Models\CreditNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CreditNoteResource extends Resource
{
    protected static ?string $model = CreditNote::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    public static function form(Schema $schema): Schema
    {
        return CreditNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditNotesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditNotes::route('/'),
            'create' => CreateCreditNote::route('/create'),
            'edit' => EditCreditNote::route('/{record}/edit'),
        ];
    }
}
