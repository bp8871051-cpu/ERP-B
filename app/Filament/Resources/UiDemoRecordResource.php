<?php

namespace App\Filament\Resources;

use App\Models\UiDemoRecord;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UiDemoRecordResource extends Resource
{
    protected static ?string $model = UiDemoRecord::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';
    protected static string|UnitEnum|null $navigationGroup = 'UI Interface';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('role')->maxLength(100),
            TextInput::make('department')->maxLength(100),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'pending' => 'Pending',
                    'suspended' => 'Suspended',
                ])
                ->default('active')
                ->required(),
            TextInput::make('salary')->numeric()->prefix('$')->default(50000),
            DatePicker::make('joined_date'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->sortable(),
                TextColumn::make('department')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('salary')->money('USD')->sortable(),
                TextColumn::make('joined_date')->date()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => UiDemoRecordResource\Pages\ListUiDemoRecords::route('/'),
        ];
    }
}
