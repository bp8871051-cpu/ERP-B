<?php

namespace App\Filament\Resources\CalendarEvents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CalendarEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('calendar_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('creator_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('meeting_link')
                    ->searchable(),
                TextColumn::make('color')
                    ->searchable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                IconColumn::make('is_all_day')
                    ->boolean(),
                IconColumn::make('is_recurring')
                    ->boolean(),
                TextColumn::make('recurrence_rule')
                    ->searchable(),
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
