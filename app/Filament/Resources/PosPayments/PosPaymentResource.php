<?php

namespace App\Filament\Resources\PosPayments;

use App\Models\PosPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class PosPaymentResource extends Resource
{
    protected static ?string $model = PosPayment::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;
}
