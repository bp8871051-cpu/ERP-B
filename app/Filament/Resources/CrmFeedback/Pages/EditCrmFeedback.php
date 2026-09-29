<?php

namespace App\Filament\Resources\CrmFeedback\Pages;

use App\Filament\Resources\CrmFeedback\CrmFeedbackResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmFeedback extends EditRecord
{
    protected static string $resource = CrmFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
