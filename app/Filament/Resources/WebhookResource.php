<?php

namespace App\Filament\Resources;

use App\Models\Webhook;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class WebhookResource extends Resource
{
    protected static ?string $model = Webhook::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bolt';
    protected static string|UnitEnum|null $navigationGroup = 'System Management';
    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('url')->url()->required(),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ])->default('active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('url')->fontFamily('mono')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_response'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => WebhookResource\Pages\ListWebhooks::route('/'),
        ];
    }
}
