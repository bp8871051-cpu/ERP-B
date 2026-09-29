<?php

namespace App\Filament\Resources\JobPositions\Pages;

use App\Filament\Resources\JobPositions\JobPositionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJobPositions extends ListRecords
{
    protected static string $resource = JobPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
