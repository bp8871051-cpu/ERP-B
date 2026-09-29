<?php

namespace App\Filament\Resources\CrmDeals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CrmDealsTable
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
                TextColumn::make('contact_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lead_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pipeline_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('stage_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('probability')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('expected_revenue')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('expected_close_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('owner_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('lost_reason')
                    ->searchable(),
                TextColumn::make('won_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('lost_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('sales_order_id')
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
