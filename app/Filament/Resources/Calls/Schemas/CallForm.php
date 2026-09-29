<?php

namespace App\Filament\Resources\Calls\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CallForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('caller_id')
                    ->required()
                    ->numeric(),
                TextInput::make('receiver_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('room_id')
                    ->default(null),
                TextInput::make('type')
                    ->required()
                    ->default('voice'),
                TextInput::make('direction')
                    ->required()
                    ->default('outgoing'),
                TextInput::make('status')
                    ->required()
                    ->default('completed'),
                DateTimePicker::make('start_time'),
                DateTimePicker::make('end_time'),
                TextInput::make('duration')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('provider')
                    ->required()
                    ->default('internal_webrtc'),
                TextInput::make('recording_url')
                    ->url()
                    ->default(null),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
