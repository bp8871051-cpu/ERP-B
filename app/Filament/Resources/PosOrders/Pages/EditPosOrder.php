<?php

namespace App\Filament\Resources\PosOrders\Pages;

use App\Filament\Resources\PosOrders\PosOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPosOrder extends EditRecord
{
    protected static string $resource = PosOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
