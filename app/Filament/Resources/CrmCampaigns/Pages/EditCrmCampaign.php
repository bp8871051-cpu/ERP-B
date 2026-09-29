<?php

namespace App\Filament\Resources\CrmCampaigns\Pages;

use App\Filament\Resources\CrmCampaigns\CrmCampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmCampaign extends EditRecord
{
    protected static string $resource = CrmCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
