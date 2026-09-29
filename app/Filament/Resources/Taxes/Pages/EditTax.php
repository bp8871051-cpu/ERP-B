<?php

namespace App\Filament\Resources\Taxes\Pages;

use App\Filament\Resources\Taxes\TaxResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTax extends EditRecord
{
    protected static string $resource = TaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
