<?php

namespace App\Filament\Resources\CalendarEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CalendarEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('calendar_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('creator_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('start_time')
                    ->required(),
                DateTimePicker::make('end_time')
                    ->required(),
                TextInput::make('timezone')
                    ->required()
                    ->default('UTC'),
                TextInput::make('location')
                    ->default(null),
                TextInput::make('meeting_link')
                    ->default(null),
                TextInput::make('color')
                    ->default(null),
                TextInput::make('category')
                    ->required()
                    ->default('meeting'),
                TextInput::make('status')
                    ->required()
                    ->default('scheduled'),
                Toggle::make('is_all_day')
                    ->required(),
                Toggle::make('is_recurring')
                    ->required(),
                TextInput::make('recurrence_rule')
                    ->default(null),
            ]);
    }
}
