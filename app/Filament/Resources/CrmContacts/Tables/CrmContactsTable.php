<?php

namespace App\Filament\Resources\CrmContacts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CrmContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('customer_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('first_name')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->searchable(),
                TextColumn::make('company_name')
                    ->searchable(),
                TextColumn::make('job_title')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('secondary_email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('whatsapp')
                    ->searchable(),
                TextColumn::make('website')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('state')
                    ->searchable(),
                TextColumn::make('country')
                    ->searchable(),
                TextColumn::make('postal_code')
                    ->searchable(),
                TextColumn::make('contact_type')
                    ->searchable(),
                TextColumn::make('lead_source_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('owner_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('last_contacted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
