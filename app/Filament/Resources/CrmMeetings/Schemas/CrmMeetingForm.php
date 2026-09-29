<?php

namespace App\Filament\Resources\CrmMeetings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class CrmMeetingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('contact_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('lead_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('deal_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                DatePicker::make('meeting_date')
                    ->required(),
                TimePicker::make('start_time'),
                TimePicker::make('end_time'),
                TextInput::make('location')
                    ->default(null),
                TextInput::make('meeting_link')
                    ->default(null),
                Textarea::make('participants')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('scheduled'),
            ]);
    }
}
