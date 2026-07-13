<?php

namespace App\Filament\Resources\CreditTransactions\Pages;

use App\Filament\Resources\CreditTransactions\CreditTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCreditTransaction extends CreateRecord
{
    protected static string $resource = CreditTransactionResource::class;
}
