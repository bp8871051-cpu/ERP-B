<?php

namespace App\Filament\Resources\Documents;

use App\Models\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Documents & Records';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;
}
