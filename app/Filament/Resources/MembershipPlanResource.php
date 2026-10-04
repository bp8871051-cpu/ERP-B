<?php

namespace App\Filament\Resources;

use App\Models\MembershipPlan;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MembershipPlanResource extends Resource
{
    protected static ?string $model = MembershipPlan::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';
    protected static string|UnitEnum|null $navigationGroup = 'Membership Management';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('code')->required()->unique(ignoreRecord: true),
            Textarea::make('description'),
            TextInput::make('price')->numeric()->prefix('$')->required(),
            Select::make('billing_cycle')
                ->options([
                    'monthly' => 'Monthly',
                    'quarterly' => 'Quarterly',
                    'half_yearly' => 'Half-Yearly',
                    'yearly' => 'Yearly',
                    'lifetime' => 'Lifetime',
                ])->default('monthly'),
            TextInput::make('max_users')->numeric()->default(5),
            TextInput::make('storage_limit_gb')->numeric()->default(50),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'draft' => 'Draft',
                ])->default('active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->badge(),
                TextColumn::make('price')->money('USD')->sortable(),
                TextColumn::make('billing_cycle')->badge(),
                TextColumn::make('max_users')->label('Seats'),
                TextColumn::make('storage_limit_gb')->label('Storage (GB)'),
                TextColumn::make('status')->badge(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => MembershipPlanResource\Pages\ListMembershipPlans::route('/'),
        ];
    }
}
