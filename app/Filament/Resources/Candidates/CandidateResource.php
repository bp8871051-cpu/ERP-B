<?php

namespace App\Filament\Resources\Candidates;

use App\Filament\Resources\Candidates\Pages\CreateCandidate;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\Candidates\Schemas\CandidateForm;
use App\Filament\Resources\Candidates\Tables\CandidatesTable;
use App\Models\Candidate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CandidateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CandidatesTable::configure($table);
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
            'index' => ListCandidates::route('/'),
            'create' => CreateCandidate::route('/create'),
            'edit' => EditCandidate::route('/{record}/edit'),
        ];
    }
}
