<?php

namespace App\Filament\Resources\DeliveryNotes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeliveryNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivery_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('salesOrder.order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('delivery_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('driver_name')
                    ->searchable(),
                TextColumn::make('vehicle_number'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => ['packed', 'dispatched'],
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'packed' => 'Packed',
                        'dispatched' => 'Dispatched',
                        'delivered' => 'Delivered',
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
