<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('po_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending_approval',
                        'info' => ['approved', 'ordered'],
                        'success' => ['received', 'completed'],
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'ordered' => 'Ordered',
                        'received' => 'Received',
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
