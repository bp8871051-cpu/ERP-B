<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('project_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('assigned_to')
                    ->numeric()
                    ->default(null),
                TextInput::make('reporter_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('priority')
                    ->required()
                    ->default('medium'),
                TextInput::make('status')
                    ->required()
                    ->default('todo'),
                TextInput::make('category')
                    ->required()
                    ->default('General'),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('reminder_at'),
                DatePicker::make('due_date'),
                DatePicker::make('start_date'),
            ]);
    }
}
