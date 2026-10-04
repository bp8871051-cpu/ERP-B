<?php

namespace App\Filament\Resources\DocumentFolders;

use App\Models\DocumentFolder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class DocumentFolderResource extends Resource
{
    protected static ?string $model = DocumentFolder::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Documents & Records';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;
}
