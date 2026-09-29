<?php

namespace App\Filament\Resources\LayoutPreferences;

use App\Filament\Resources\LayoutPreferences\Pages\CreateLayoutPreference;
use App\Filament\Resources\LayoutPreferences\Pages\EditLayoutPreference;
use App\Filament\Resources\LayoutPreferences\Pages\ListLayoutPreferences;
use App\Filament\Resources\LayoutPreferences\Schemas\LayoutPreferenceForm;
use App\Filament\Resources\LayoutPreferences\Tables\LayoutPreferencesTable;
use App\Models\UserLayoutPreference;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LayoutPreferenceResource extends Resource
{
    protected static ?string $model = UserLayoutPreference::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Layout Settings';

    public static function form(Schema $schema): Schema
    {
        return LayoutPreferenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LayoutPreferencesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLayoutPreferences::route('/'),
            'create' => CreateLayoutPreference::route('/create'),
            'edit' => EditLayoutPreference::route('/{record}/edit'),
        ];
    }
}
