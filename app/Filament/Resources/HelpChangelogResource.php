<?php

namespace App\Filament\Resources;

use App\Models\HelpChangelog;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class HelpChangelogResource extends Resource
{
    protected static ?string $model = HelpChangelog::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static string|UnitEnum|null $navigationGroup = 'Help & Support';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('version')->required()->maxLength(50),
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('tag')->default('Stable')->maxLength(50),
            DatePicker::make('release_date')->required(),
            Textarea::make('description')->rows(3),
            Select::make('status')
                ->options([
                    'published' => 'Published',
                    'draft' => 'Draft',
                ])
                ->default('published')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version')->searchable()->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('tag')->badge(),
                TextColumn::make('release_date')->date()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\HelpChangelogResource\Pages\ListHelpChangelogs::route('/'),
        ];
    }
}
