<?php

namespace App\Filament\Resources\Purchases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchase_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('paid_amount')
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
                        'success' => 'received',
                        'info' => 'partially_paid',
                        'primary' => 'paid',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'received' => 'Received',
                        'partially_paid' => 'Partially Paid',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
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
