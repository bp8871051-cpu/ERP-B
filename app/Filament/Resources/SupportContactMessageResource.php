<?php

namespace App\Filament\Resources;

use App\Models\SupportContactMessage;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SupportContactMessageResource extends Resource
{
    protected static ?string $model = SupportContactMessage::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|UnitEnum|null $navigationGroup = 'Support Management';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),
            TextInput::make('phone'),
            TextInput::make('subject')->required(),
            Textarea::make('message')->required(),
            Select::make('status')
                ->options([
                    'new' => 'New',
                    'read' => 'Read',
                    'replied' => 'Replied',
                    'converted' => 'Converted',
                    'archived' => 'Archived',
                    'spam' => 'Spam',
                ])->default('new'),
            TextInput::make('source')->default('website'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('subject')->limit(30),
                TextColumn::make('source')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'read',
                        'success' => 'replied',
                        'info' => 'converted',
                        'danger' => 'spam',
                    ]),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => SupportContactMessageResource\Pages\ListSupportContactMessages::route('/'),
        ];
    }
}
