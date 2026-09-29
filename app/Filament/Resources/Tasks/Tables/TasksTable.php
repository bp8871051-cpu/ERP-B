<?php

namespace App\Filament\Resources\Tasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('project_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('assigned_to')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reporter_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('priority')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reminder_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
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
