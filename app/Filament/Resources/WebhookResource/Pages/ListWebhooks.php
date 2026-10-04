<?php

namespace App\Filament\Resources\WebhookResource\Pages;

use App\Filament\Resources\WebhookResource;
use Filament\Resources\Pages\ListRecords;

class ListWebhooks extends ListRecords
{
    protected static string $resource = WebhookResource::class;
}
