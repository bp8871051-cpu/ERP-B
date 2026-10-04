<?php

namespace App\Filament\Resources\AssetDepreciations;

use App\Models\AssetDepreciation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class AssetDepreciationResource extends Resource
{
    protected static ?string $model = AssetDepreciation::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Fixed Assets';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;
}
