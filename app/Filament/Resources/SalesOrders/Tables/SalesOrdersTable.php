<?php

namespace App\Filament\Resources\SalesOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'partial',
                        'danger' => 'unpaid',
                    ]),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending',
                        'info' => ['confirmed', 'processing'],
                        'success' => ['delivered', 'completed'],
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partially Paid',
                        'paid' => 'Paid',
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
