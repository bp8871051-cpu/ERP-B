<?php

namespace App\Filament\Resources\PosRefunds;

use App\Models\PosRefund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class PosRefundResource extends Resource
{
    protected static ?string $model = PosRefund::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;
}
