<?php

namespace App\Filament\Resources\AssetMaintenance;

use App\Models\AssetMaintenance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class AssetMaintenanceResource extends Resource
{
    protected static ?string $model = AssetMaintenance::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Fixed Assets';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;
}
