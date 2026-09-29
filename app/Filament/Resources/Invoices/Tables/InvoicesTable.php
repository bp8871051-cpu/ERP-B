<?php

namespace App\Filament\Resources\Invoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('due_amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'partial',
                        'danger' => ['unpaid', 'overdue'],
                    ]),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partially Paid',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
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
