<?php

namespace App\Filament\Resources\Conversations;

use App\Filament\Resources\Conversations\Pages\CreateConversation;
use App\Filament\Resources\Conversations\Pages\EditConversation;
use App\Filament\Resources\Conversations\Pages\ListConversations;
use App\Filament\Resources\Conversations\Schemas\ConversationForm;
use App\Filament\Resources\Conversations\Tables\ConversationsTable;
use App\Models\Conversation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Applications';

    public static function form(Schema $schema): Schema
    {
        return ConversationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConversationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
            'create' => CreateConversation::route('/create'),
            'edit' => EditConversation::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
