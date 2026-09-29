<?php

namespace App\Filament\Resources\Refunds\Pages;

use App\Filament\Resources\Refunds\RefundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRefund extends EditRecord
{
    protected static string $resource = RefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
