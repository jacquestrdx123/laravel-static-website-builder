<?php

namespace App\Filament\Resources\Websites\Tables;

use App\Models\Website;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WebsitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Website::STATUS_DRAFT => 'gray',
                        Website::STATUS_QUEUED => 'warning',
                        Website::STATUS_GENERATING => 'info',
                        Website::STATUS_READY => 'success',
                        Website::STATUS_FAILED => 'danger',
                        Website::STATUS_PUBLISHED => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('preview')
                    ->label('Preview')
                    ->state('View')
                    ->url(fn (Website $record): ?string => $record->isGenerated() ? $record->previewUrl() : null)
                    ->openUrlInNewTab()
                    ->color('primary'),
                TextColumn::make('custom_domain')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('generated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
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
                        Website::STATUS_DRAFT => 'Draft',
                        Website::STATUS_QUEUED => 'Queued',
                        Website::STATUS_GENERATING => 'Generating',
                        Website::STATUS_READY => 'Ready',
                        Website::STATUS_FAILED => 'Failed',
                        Website::STATUS_PUBLISHED => 'Published',
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
