<?php

namespace App\Filament\Resources\WorkflowRequests\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WorkflowRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('workflow_id')
                    ->required()
                    ->numeric(),
                TextInput::make('requester_id')
                    ->required()
                    ->numeric(),
                TextInput::make('reference_number')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('module')
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->default(null),
                Textarea::make('data')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('current_step_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                DatePicker::make('due_date'),
            ]);
    }
}
