<?php

namespace App\Filament\Resources\CreditTransactions;

use App\Filament\Resources\CreditTransactions\Pages\ListCreditTransactions;
use App\Filament\Resources\CreditTransactions\Pages\ViewCreditTransaction;
use App\Filament\Resources\CreditTransactions\Schemas\CreditTransactionInfolist;
use App\Filament\Resources\CreditTransactions\Tables\CreditTransactionsTable;
use App\Models\CreditTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CreditTransactionResource extends Resource
{
    protected static ?string $model = CreditTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Credit Ledger';

    protected static ?string $modelLabel = 'credit transaction';

    protected static ?string $pluralModelLabel = 'credit transactions';

    public static function infolist(Schema $schema): Schema
    {
        return CreditTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditTransactionsTable::configure($table);
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
            'index' => ListCreditTransactions::route('/'),
            'view' => ViewCreditTransaction::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
