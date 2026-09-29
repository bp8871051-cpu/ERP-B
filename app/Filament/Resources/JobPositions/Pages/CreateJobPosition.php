<?php

namespace App\Filament\Resources\JobPositions\Pages;

use App\Filament\Resources\JobPositions\JobPositionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobPosition extends CreateRecord
{
    protected static string $resource = JobPositionResource::class;
}
