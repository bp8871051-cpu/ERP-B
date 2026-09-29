<?php

namespace App\Filament\Resources\CrmPipelines\Pages;

use App\Filament\Resources\CrmPipelines\CrmPipelineResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmPipeline extends EditRecord
{
    protected static string $resource = CrmPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
