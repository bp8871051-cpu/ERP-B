<?php

namespace App\Filament\Resources\DocumentWorkflows;

use App\Models\DocumentWorkflow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class DocumentWorkflowResource extends Resource
{
    protected static ?string $model = DocumentWorkflow::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Documents & Records';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;
}
