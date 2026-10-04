<?php

namespace App\Filament\Resources\DocumentCompliance;

use App\Models\DocumentCompliance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class DocumentComplianceResource extends Resource
{
    protected static ?string $model = DocumentCompliance::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Documents & Records';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
}
