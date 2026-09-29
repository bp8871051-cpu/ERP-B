<?php

namespace App\Filament\Resources\WorkflowRequests\Pages;

use App\Filament\Resources\WorkflowRequests\WorkflowRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkflowRequests extends ListRecords
{
    protected static string $resource = WorkflowRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
