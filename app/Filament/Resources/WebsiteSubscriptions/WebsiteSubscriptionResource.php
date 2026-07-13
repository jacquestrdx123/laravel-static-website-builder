<?php

namespace App\Filament\Resources\WebsiteSubscriptions;

use App\Filament\Resources\WebsiteSubscriptions\Pages\ListWebsiteSubscriptions;
use App\Filament\Resources\WebsiteSubscriptions\Tables\WebsiteSubscriptionsTable;
use App\Models\WebsiteSubscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WebsiteSubscriptionResource extends Resource
{
    protected static ?string $model = WebsiteSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Editing subscriptions';

    public static function table(Table $table): Table
    {
        return WebsiteSubscriptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebsiteSubscriptions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
