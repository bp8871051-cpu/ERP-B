<?php

namespace App\Filament\Resources\CrmPipelines\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CrmPipelineForm
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
                TextInput::make('code')
                    ->default(null),
                Toggle::make('is_default')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
