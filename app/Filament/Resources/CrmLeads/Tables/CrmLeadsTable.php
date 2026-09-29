<?php

namespace App\Filament\Resources\CrmLeads\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CrmLeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('contact_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('customer_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('company_name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('job_title')
                    ->searchable(),
                TextColumn::make('website')
                    ->searchable(),
                TextColumn::make('lead_source_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('campaign_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('owner_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('industry')
                    ->searchable(),
                TextColumn::make('company_size')
                    ->searchable(),
                TextColumn::make('budget')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('expected_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('expected_close_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('score_category')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('lost_reason')
                    ->searchable(),
                TextColumn::make('converted_at')
                    ->dateTime()
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
