<?php

namespace App\Filament\Resources\WebsiteSubscriptions\Tables;

use App\Models\WebsiteSubscription;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WebsiteSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->searchable()->sortable(),
                TextColumn::make('website.name')->searchable()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        WebsiteSubscription::STATUS_ACTIVE => 'success',
                        WebsiteSubscription::STATUS_EXPIRED => 'warning',
                        WebsiteSubscription::STATUS_CANCELLED => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('expires_at')->dateTime()->sortable(),
                TextColumn::make('note')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    WebsiteSubscription::STATUS_ACTIVE => 'Active',
                    WebsiteSubscription::STATUS_EXPIRED => 'Expired',
                    WebsiteSubscription::STATUS_CANCELLED => 'Cancelled',
                ]),
            ]);
    }
}
