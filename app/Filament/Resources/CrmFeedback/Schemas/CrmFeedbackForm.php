<?php

namespace App\Filament\Resources\CrmFeedback\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CrmFeedbackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('customer_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('contact_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('deal_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(5),
                TextInput::make('category')
                    ->required()
                    ->default('service'),
                Textarea::make('feedback_text')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('new'),
                TextInput::make('assigned_to')
                    ->numeric()
                    ->default(null),
                Textarea::make('resolution')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('resolved_at'),
            ]);
    }
}
