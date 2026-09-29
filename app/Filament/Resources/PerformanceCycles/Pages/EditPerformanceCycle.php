<?php

namespace App\Filament\Resources\PerformanceCycles\Pages;

use App\Filament\Resources\PerformanceCycles\PerformanceCycleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPerformanceCycle extends EditRecord
{
    protected static string $resource = PerformanceCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
