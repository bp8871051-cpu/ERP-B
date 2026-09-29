<?php

namespace App\Filament\Resources\Workflows\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WorkflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('module')
                    ->required(),
                TextInput::make('trigger')
                    ->required()
                    ->default('on_submit'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('created_by')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
