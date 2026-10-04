<?php

namespace App\Filament\Resources\DocumentApprovals;

use App\Models\DocumentApproval;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class DocumentApprovalResource extends Resource
{
    protected static ?string $model = DocumentApproval::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Documents & Records';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;
}
