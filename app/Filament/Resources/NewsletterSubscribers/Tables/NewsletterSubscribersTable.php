<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use App\Models\NewsletterSubscriber;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('website.name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        NewsletterSubscriber::STATUS_SUBSCRIBED => 'success',
                        NewsletterSubscriber::STATUS_UNSUBSCRIBED => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('subscribed_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    NewsletterSubscriber::STATUS_SUBSCRIBED => 'Subscribed',
                    NewsletterSubscriber::STATUS_UNSUBSCRIBED => 'Unsubscribed',
                ]),
            ]);
    }
}
