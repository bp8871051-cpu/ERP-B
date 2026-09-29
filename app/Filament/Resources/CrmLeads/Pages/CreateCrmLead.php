<?php

namespace App\Filament\Resources\CrmLeads\Pages;

use App\Filament\Resources\CrmLeads\CrmLeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrmLead extends CreateRecord
{
    protected static string $resource = CrmLeadResource::class;
}
