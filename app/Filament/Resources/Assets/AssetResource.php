<?php

namespace App\Filament\Resources\Assets;

use App\Models\Asset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Fixed Assets';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;
}
