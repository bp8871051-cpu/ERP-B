<?php

namespace App\Filament\Resources\PosOrders\Pages;

use App\Filament\Resources\PosOrders\PosOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosOrders extends ListRecords
{
    protected static string $resource = PosOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
