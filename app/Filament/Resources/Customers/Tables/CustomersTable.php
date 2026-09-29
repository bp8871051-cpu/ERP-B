<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone'),
                TextColumn::make('city')
                    ->sortable(),
                TextColumn::make('credit_limit')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
