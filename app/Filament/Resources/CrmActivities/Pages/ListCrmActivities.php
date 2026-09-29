<?php

namespace App\Filament\Resources\CrmActivities\Pages;

use App\Filament\Resources\CrmActivities\CrmActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrmActivities extends ListRecords
{
    protected static string $resource = CrmActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
