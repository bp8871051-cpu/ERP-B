<?php

namespace App\Filament\Resources\WorkflowRequests\Pages;

use App\Filament\Resources\WorkflowRequests\WorkflowRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkflowRequest extends EditRecord
{
    protected static string $resource = WorkflowRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
