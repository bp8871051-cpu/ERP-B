<?php

namespace App\Filament\Resources\EmailAccounts\Pages;

use App\Filament\Resources\EmailAccounts\EmailAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmailAccounts extends ListRecords
{
    protected static string $resource = EmailAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
