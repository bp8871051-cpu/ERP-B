<?php

namespace App\Filament\Resources\CreditNotes\Pages;

use App\Filament\Resources\CreditNotes\CreditNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCreditNote extends EditRecord
{
    protected static string $resource = CreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
