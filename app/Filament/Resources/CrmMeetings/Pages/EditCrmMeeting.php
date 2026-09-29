<?php

namespace App\Filament\Resources\CrmMeetings\Pages;

use App\Filament\Resources\CrmMeetings\CrmMeetingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmMeeting extends EditRecord
{
    protected static string $resource = CrmMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
