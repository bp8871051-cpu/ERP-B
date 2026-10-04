<?php

namespace App\Filament\Resources;

use App\Models\KnowledgeBaseArticle;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class KnowledgeBaseResource extends Resource
{
    protected static ?string $model = KnowledgeBaseArticle::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static string|UnitEnum|null $navigationGroup = 'Support Management';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            TextInput::make('slug'),
            Textarea::make('description'),
            Textarea::make('content')->required(),
            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'review' => 'Review',
                    'published' => 'Published',
                    'archived' => 'Archived',
                ])->default('published'),
            Select::make('visibility')
                ->options([
                    'all' => 'Public / All',
                    'internal' => 'Internal Staff',
                    'customers' => 'Customers Only',
                ])->default('all'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category.name')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('view_count')->sortable(),
                TextColumn::make('helpful_count')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => KnowledgeBaseResource\Pages\ListKnowledgeBaseArticles::route('/'),
        ];
    }
}
