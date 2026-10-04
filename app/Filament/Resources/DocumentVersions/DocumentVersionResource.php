<?php

namespace App\Filament\Resources\DocumentVersions;

use App\Models\DocumentVersion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class DocumentVersionResource extends Resource
{
    protected static ?string $model = DocumentVersion::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Documents & Records';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
}
