<?php

namespace App\Filament\Resources\FileItems\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FileItemForm
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
                TextInput::make('folder_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('name')
                    ->required(),
                TextInput::make('disk')
                    ->required()
                    ->default('local'),
                TextInput::make('file_path')
                    ->required(),
                TextInput::make('file_type')
                    ->required(),
                TextInput::make('mime_type')
                    ->default(null),
                TextInput::make('file_size')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_favorite')
                    ->required(),
                Toggle::make('is_recent')
                    ->required(),
                TextInput::make('download_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
