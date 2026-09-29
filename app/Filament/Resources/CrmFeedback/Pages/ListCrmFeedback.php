<?php

namespace App\Filament\Resources\CrmFeedback\Pages;

use App\Filament\Resources\CrmFeedback\CrmFeedbackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrmFeedback extends ListRecords
{
    protected static string $resource = CrmFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
