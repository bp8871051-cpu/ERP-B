<?php

namespace App\Filament\Resources\WorkflowRequests\Pages;

use App\Filament\Resources\WorkflowRequests\WorkflowRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkflowRequest extends CreateRecord
{
    protected static string $resource = WorkflowRequestResource::class;
}
