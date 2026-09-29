<?php

namespace App\Filament\Resources\LayoutPreferences\Pages;

use App\Filament\Resources\LayoutPreferences\LayoutPreferenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLayoutPreference extends EditRecord
{
    protected static string $resource = LayoutPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
