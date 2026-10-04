<?php

namespace App\Filament\Resources\HelpChangelogResource\Pages;

use App\Filament\Resources\HelpChangelogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHelpChangelogs extends ListRecords
{
    protected static string $resource = HelpChangelogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
