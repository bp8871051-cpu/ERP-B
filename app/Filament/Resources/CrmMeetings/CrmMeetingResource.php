<?php

namespace App\Filament\Resources\CrmMeetings;

use App\Filament\Resources\CrmMeetings\Pages\CreateCrmMeeting;
use App\Filament\Resources\CrmMeetings\Pages\EditCrmMeeting;
use App\Filament\Resources\CrmMeetings\Pages\ListCrmMeetings;
use App\Filament\Resources\CrmMeetings\Schemas\CrmMeetingForm;
use App\Filament\Resources\CrmMeetings\Tables\CrmMeetingsTable;
use App\Models\CrmMeeting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CrmMeetingResource extends Resource
{
    protected static ?string $model = CrmMeeting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CrmMeetingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmMeetingsTable::configure($table);
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
            'index' => ListCrmMeetings::route('/'),
            'create' => CreateCrmMeeting::route('/create'),
            'edit' => EditCrmMeeting::route('/{record}/edit'),
        ];
    }
}
