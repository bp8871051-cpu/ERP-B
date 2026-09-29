<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('role')
                    ->options([
                        'Super Admin' => 'Super Admin',
                        'Admin' => 'Admin',
                        'HR Manager' => 'HR Manager',
                        'Inventory Manager' => 'Inventory Manager',
                        'CRM Manager' => 'CRM Manager',
                        'Sales Manager' => 'Sales Manager',
                        'Finance Manager' => 'Finance Manager',
                        'Procurement Manager' => 'Procurement Manager',
                        'Project Manager' => 'Project Manager',
                        'Support Manager' => 'Support Manager',
                        'Employee' => 'Employee',
                    ])
                    ->default('Employee')
                    ->required(),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(50),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
