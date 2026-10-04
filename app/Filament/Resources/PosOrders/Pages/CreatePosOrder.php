<?php

namespace App\Filament\Resources\PosOrders\Pages;

use App\Filament\Resources\PosOrders\PosOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePosOrder extends CreateRecord
{
    protected static string $resource = PosOrderResource::class;
}
