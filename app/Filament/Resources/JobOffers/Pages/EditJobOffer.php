<?php

namespace App\Filament\Resources\JobOffers\Pages;

use App\Filament\Resources\JobOffers\JobOfferResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJobOffer extends EditRecord
{
    protected static string $resource = JobOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
