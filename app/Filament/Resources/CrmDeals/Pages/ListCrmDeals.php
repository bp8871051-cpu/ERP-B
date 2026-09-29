<?php

namespace App\Filament\Resources\CrmDeals\Pages;

use App\Filament\Resources\CrmDeals\CrmDealResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrmDeals extends ListRecords
{
    protected static string $resource = CrmDealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
