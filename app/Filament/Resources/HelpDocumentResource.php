<?php

namespace App\Filament\Resources;

use App\Models\HelpDocument;
use App\Models\HelpDocumentCategory;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class HelpDocumentResource extends Resource
{
    protected static ?string $model = HelpDocument::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static string|UnitEnum|null $navigationGroup = 'Help & Support';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category_id')
                ->label('Category')
                ->options(HelpDocumentCategory::pluck('name', 'id'))
                ->required(),
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            TextInput::make('read_time')->default('5 min'),
            Textarea::make('excerpt')->rows(2),
            Textarea::make('content')->required()->rows(6),
            Select::make('status')
                ->options([
                    'published' => 'Published',
                    'draft' => 'Draft',
                ])
                ->default('published')
                ->required(),
            TextInput::make('sort_order')->numeric()->default(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('read_time'),
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\HelpDocumentResource\Pages\ListHelpDocuments::route('/'),
        ];
    }
}
