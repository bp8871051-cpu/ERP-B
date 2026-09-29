<?php

namespace App\Filament\Resources\LayoutPreferences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LayoutPreferencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sidebar_mode')
                    ->label('Sidebar Mode')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'mini' => 'info',
                        'hover' => 'warning',
                        'hidden' => 'danger',
                        default => 'success',
                    })
                    ->sortable(),
                TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'rtl' ? 'warning' : 'primary')
                    ->sortable(),
                TextColumn::make('content_width')
                    ->label('Width')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sidebar_mode')
                    ->options([
                        'default' => 'Default',
                        'mini' => 'Mini',
                        'hover' => 'Hover',
                        'hidden' => 'Hidden',
                    ]),
                SelectFilter::make('direction')
                    ->options([
                        'ltr' => 'LTR',
                        'rtl' => 'RTL',
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
