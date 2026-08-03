<?php

namespace App\Filament\Resources\PromoCodes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('credits_granted')
                    ->placeholder('—'),
                TextColumn::make('discount_applied_zar')
                    ->label('Discount (ZAR)')
                    ->placeholder('—'),
                TextColumn::make('pack_credits')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Redeemed at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
