<?php

namespace App\Filament\Resources\AssetDisposals;

use App\Models\AssetDisposal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class AssetDisposalResource extends Resource
{
    protected static ?string $model = AssetDisposal::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Fixed Assets';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxXMark;
}
