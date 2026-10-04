<?php

namespace App\Filament\Resources\AssetAssignments;

use App\Models\AssetAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class AssetAssignmentResource extends Resource
{
    protected static ?string $model = AssetAssignment::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Fixed Assets';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;
}
