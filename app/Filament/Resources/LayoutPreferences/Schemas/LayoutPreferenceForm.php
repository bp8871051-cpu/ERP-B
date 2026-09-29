<?php

namespace App\Filament\Resources\LayoutPreferences\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LayoutPreferenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Select::make('sidebar_mode')
                    ->options([
                        'default' => 'Default (Expanded)',
                        'mini' => 'Mini Sidebar (Icons only)',
                        'hover' => 'Hover View (Expand on hover)',
                        'hidden' => 'Hidden Menu (Overlay drawer)',
                    ])
                    ->required()
                    ->default('default'),
                Select::make('menu_behavior')
                    ->options([
                        'click' => 'Click to expand',
                        'hover' => 'Hover to expand',
                    ])
                    ->required()
                    ->default('click'),
                Select::make('content_width')
                    ->options([
                        'default' => 'Default (Boxed / Centered)',
                        'full' => 'Full Width (100% viewport)',
                    ])
                    ->required()
                    ->default('default'),
                Select::make('direction')
                    ->options([
                        'ltr' => 'Left-to-Right (LTR)',
                        'rtl' => 'Right-to-Left (RTL)',
                    ])
                    ->required()
                    ->default('ltr'),
                Select::make('sidebar_visibility')
                    ->options([
                        'visible' => 'Visible',
                        'hidden' => 'Hidden',
                    ])
                    ->required()
                    ->default('visible'),
                Select::make('sidebar_state')
                    ->options([
                        'expanded' => 'Expanded',
                        'collapsed' => 'Collapsed',
                    ])
                    ->required()
                    ->default('expanded'),
            ]);
    }
}
