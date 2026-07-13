<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Websites\WebsiteResource;
use App\Models\Website;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestWebsites extends TableWidget
{
    protected static ?string $heading = 'Latest Websites';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Website::query()
                    ->with('user')
                    ->latest()
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Owner'),
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
                    }),
                TextColumn::make('created_at')
                    ->since(),
            ])
            ->paginated(false)
            ->recordUrl(fn (Website $record): string => WebsiteResource::getUrl('edit', ['record' => $record]));
    }
}
