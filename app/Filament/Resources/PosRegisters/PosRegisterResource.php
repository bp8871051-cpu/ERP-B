<?php

namespace App\Filament\Resources\PosRegisters;

use App\Models\PosRegister;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class PosRegisterResource extends Resource
{
    protected static ?string $model = PosRegister::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;
}
