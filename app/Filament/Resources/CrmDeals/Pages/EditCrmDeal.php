<?php

namespace App\Filament\Resources\CrmDeals\Pages;

use App\Filament\Resources\CrmDeals\CrmDealResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmDeal extends EditRecord
{
    protected static string $resource = CrmDealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
