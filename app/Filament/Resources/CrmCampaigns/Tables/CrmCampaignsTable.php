<?php

namespace App\Filament\Resources\CrmCampaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CrmCampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('owner_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('budget')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('total_audience')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sent_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('delivered_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('opened_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clicked_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('converted_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('revenue')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('roi')
                    ->numeric()
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
