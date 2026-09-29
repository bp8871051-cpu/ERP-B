<?php

namespace App\Filament\Resources\PurchaseReturns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchase.purchase_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('return_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'requested',
                        'info' => 'approved',
                        'success' => ['returned', 'completed'],
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'requested' => 'Requested',
                        'approved' => 'Approved',
                        'returned' => 'Returned',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
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
