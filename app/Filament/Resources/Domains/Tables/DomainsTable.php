<?php

namespace App\Filament\Resources\Domains\Tables;

use App\Models\Domain;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('domain')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('website.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Domain::STATUS_ACTIVE => 'success',
                        Domain::STATUS_PENDING => 'warning',
                        Domain::STATUS_EXPIRED => 'danger',
                        Domain::STATUS_TRANSFERRED => 'info',
                        Domain::STATUS_CANCELLED => 'gray',
                        Domain::STATUS_FAILED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('id_protection')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('registrar_locked')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Domain::STATUS_PENDING => 'Pending',
                        Domain::STATUS_ACTIVE => 'Active',
                        Domain::STATUS_EXPIRED => 'Expired',
                        Domain::STATUS_TRANSFERRED => 'Transferred',
                        Domain::STATUS_CANCELLED => 'Cancelled',
                        Domain::STATUS_FAILED => 'Failed',
                    ]),
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
