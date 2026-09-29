<?php

namespace App\Filament\Resources\LayoutPreferences\Pages;

use App\Filament\Resources\LayoutPreferences\LayoutPreferenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLayoutPreferences extends ListRecords
{
    protected static string $resource = LayoutPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
