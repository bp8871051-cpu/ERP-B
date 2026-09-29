<?php

namespace App\Filament\Resources\CrmMeetings\Pages;

use App\Filament\Resources\CrmMeetings\CrmMeetingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrmMeetings extends ListRecords
{
    protected static string $resource = CrmMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
