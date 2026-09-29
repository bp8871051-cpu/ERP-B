<?php

namespace App\Filament\Resources\EmailAccounts\Pages;

use App\Filament\Resources\EmailAccounts\EmailAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailAccount extends CreateRecord
{
    protected static string $resource = EmailAccountResource::class;
}
