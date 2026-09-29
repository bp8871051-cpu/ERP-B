<?php

namespace App\Filament\Resources\JobOffers\Pages;

use App\Filament\Resources\JobOffers\JobOfferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJobOffers extends ListRecords
{
    protected static string $resource = JobOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
