<?php

namespace App\Filament\Resources\CreditNotes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CreditNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('credit_note_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('credit_note_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'issued',
                        'success' => 'applied',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'applied' => 'Applied',
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
