<?php

namespace App\Filament\Resources\CrmLeads\Pages;

use App\Filament\Resources\CrmLeads\CrmLeadResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmLead extends EditRecord
{
    protected static string $resource = CrmLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
