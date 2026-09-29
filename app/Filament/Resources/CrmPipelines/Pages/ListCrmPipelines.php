<?php

namespace App\Filament\Resources\CrmPipelines\Pages;

use App\Filament\Resources\CrmPipelines\CrmPipelineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrmPipelines extends ListRecords
{
    protected static string $resource = CrmPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
