<?php

namespace App\Filament\Resources\Refunds\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RefundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('refund_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('refund_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('refund_amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('refund_method')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'requested',
                        'info' => 'approved',
                        'success' => 'processed',
                        'danger' => 'rejected',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'requested' => 'Requested',
                        'approved' => 'Approved',
                        'processed' => 'Processed',
                        'rejected' => 'Rejected',
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
