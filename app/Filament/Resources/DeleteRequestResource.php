<?php

namespace App\Filament\Resources;

use App\Models\DeleteRequest;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DeleteRequestResource extends Resource
{
    protected static ?string $model = DeleteRequest::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-trash';
    protected static string|UnitEnum|null $navigationGroup = 'System Management';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('module')->required(),
            TextInput::make('record_type')->required(),
            TextInput::make('record_id')->required(),
            TextInput::make('record_name'),
            Textarea::make('reason')->required(),
            Select::make('status')
                ->options([
                    'pending' => 'Pending Review',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'completed' => 'Completed',
                ])->default('pending'),
            Textarea::make('review_notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Req #')->sortable(),
                TextColumn::make('user.name')->label('Requester')->searchable(),
                TextColumn::make('module')->badge(),
                TextColumn::make('record_type'),
                TextColumn::make('record_name'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => DeleteRequestResource\Pages\ListDeleteRequests::route('/'),
        ];
    }
}
