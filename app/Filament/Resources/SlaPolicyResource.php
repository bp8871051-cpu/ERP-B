<?php

namespace App\Filament\Resources;

use App\Models\SupportSlaPolicy;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SlaPolicyResource extends Resource
{
    protected static ?string $model = SupportSlaPolicy::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static string|UnitEnum|null $navigationGroup = 'Support Management';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            Textarea::make('description'),
            Select::make('priority')
                ->options([
                    'urgent' => 'Urgent',
                    'high' => 'High',
                    'medium' => 'Medium',
                    'low' => 'Low',
                ])->required(),
            TextInput::make('first_response_target_minutes')->numeric()->required(),
            TextInput::make('resolution_target_minutes')->numeric()->required(),
            Toggle::make('business_hours')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('first_response_target_minutes')->label('First Response (mins)'),
                TextColumn::make('resolution_target_minutes')->label('Resolution (mins)'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => SlaPolicyResource\Pages\ListSlaPolicies::route('/'),
        ];
    }
}
