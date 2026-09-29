<?php

namespace App\Filament\Resources\DeliveryNotes\Pages;

use App\Filament\Resources\DeliveryNotes\DeliveryNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryNote extends EditRecord
{
    protected static string $resource = DeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
