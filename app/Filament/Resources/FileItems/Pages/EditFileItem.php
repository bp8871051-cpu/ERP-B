<?php

namespace App\Filament\Resources\FileItems\Pages;

use App\Filament\Resources\FileItems\FileItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditFileItem extends EditRecord
{
    protected static string $resource = FileItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
