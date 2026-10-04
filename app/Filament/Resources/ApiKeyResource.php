<?php

namespace App\Filament\Resources;

use App\Models\ApiKey;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';
    protected static string|UnitEnum|null $navigationGroup = 'System Management';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('key')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('key')->fontFamily('mono'),
                TextColumn::make('last_used_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ApiKeyResource\Pages\ListApiKeys::route('/'),
        ];
    }
}
