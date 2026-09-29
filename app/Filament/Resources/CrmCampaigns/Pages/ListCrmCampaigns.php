<?php

namespace App\Filament\Resources\CrmCampaigns\Pages;

use App\Filament\Resources\CrmCampaigns\CrmCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrmCampaigns extends ListRecords
{
    protected static string $resource = CrmCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
