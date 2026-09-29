<?php

namespace App\Filament\Resources\SalesQuotes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesQuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quote_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quote_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'success' => ['accepted', 'converted'],
                        'danger' => ['rejected', 'expired'],
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                        'converted' => 'Converted',
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
