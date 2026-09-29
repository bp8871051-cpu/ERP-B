<?php

namespace App\Filament\Resources\EmailAccounts\Pages;

use App\Filament\Resources\EmailAccounts\EmailAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailAccount extends EditRecord
{
    protected static string $resource = EmailAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
