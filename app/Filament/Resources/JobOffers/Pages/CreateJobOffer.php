<?php

namespace App\Filament\Resources\JobOffers\Pages;

use App\Filament\Resources\JobOffers\JobOfferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobOffer extends CreateRecord
{
    protected static string $resource = JobOfferResource::class;
}
