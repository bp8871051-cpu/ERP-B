<?php

namespace App\Filament\Resources\FileItems\Pages;

use App\Filament\Resources\FileItems\FileItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFileItems extends ListRecords
{
    protected static string $resource = FileItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
