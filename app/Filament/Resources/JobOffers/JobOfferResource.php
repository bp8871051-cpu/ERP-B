<?php

namespace App\Filament\Resources\JobOffers;

use App\Filament\Resources\JobOffers\Pages\CreateJobOffer;
use App\Filament\Resources\JobOffers\Pages\EditJobOffer;
use App\Filament\Resources\JobOffers\Pages\ListJobOffers;
use App\Filament\Resources\JobOffers\Schemas\JobOfferForm;
use App\Filament\Resources\JobOffers\Tables\JobOffersTable;
use App\Models\JobOffer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JobOfferResource extends Resource
{
    protected static ?string $model = JobOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return JobOfferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobOffersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobOffers::route('/'),
            'create' => CreateJobOffer::route('/create'),
            'edit' => EditJobOffer::route('/{record}/edit'),
        ];
    }
}
