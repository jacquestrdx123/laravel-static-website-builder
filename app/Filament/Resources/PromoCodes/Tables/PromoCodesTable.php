<?php

namespace App\Filament\Resources\PromoCodes\Tables;

use App\Models\PromoCode;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromoCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PromoCode::TYPE_CREDITS => 'Credits',
                        PromoCode::TYPE_CHECKOUT_DISCOUNT => 'Checkout discount',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        PromoCode::TYPE_CREDITS => 'success',
                        PromoCode::TYPE_CHECKOUT_DISCOUNT => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('reward')
                    ->label('Reward')
                    ->state(fn (PromoCode $record): string => $record->rewardSummary()),
                TextColumn::make('times_redeemed')
                    ->label('Redeemed')
                    ->sortable()
                    ->formatStateUsing(function (PromoCode $record): string {
                        $used = $record->times_redeemed;
                        $max = $record->max_redemptions;

                        return $max === null ? (string) $used : $used.'/'.$max;
                    }),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        PromoCode::TYPE_CREDITS => 'Credits',
                        PromoCode::TYPE_CHECKOUT_DISCOUNT => 'Checkout discount',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
