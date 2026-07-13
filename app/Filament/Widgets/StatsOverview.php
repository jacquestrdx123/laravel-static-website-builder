<?php

namespace App\Filament\Widgets;

use App\Models\CreditTransaction;
use App\Models\User;
use App\Models\Website;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $publishedCount = Website::query()->where('status', Website::STATUS_PUBLISHED)->count();
        $failedCount = Website::query()->where('status', Website::STATUS_FAILED)->count();

        return [
            Stat::make('Users', User::query()->count())
                ->description('Registered accounts')
                ->icon('heroicon-o-users'),
            Stat::make('Websites', Website::query()->count())
                ->description("{$publishedCount} published, {$failedCount} failed")
                ->icon('heroicon-o-globe-alt'),
            Stat::make('Credits in circulation', User::query()->sum('ai_credits'))
                ->description('Total AI credits across all users')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Credit transactions', CreditTransaction::query()->count())
                ->description('Ledger entries')
                ->icon('heroicon-o-receipt-percent'),
        ];
    }
}
