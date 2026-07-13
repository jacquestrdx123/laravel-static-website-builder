<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\Website;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WebsitesRelationManager extends RelationManager
{
    protected static string $relationship = 'websites';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('status')
                    ->options([
                        Website::STATUS_DRAFT => 'Draft',
                        Website::STATUS_QUEUED => 'Queued',
                        Website::STATUS_GENERATING => 'Generating',
                        Website::STATUS_READY => 'Ready',
                        Website::STATUS_FAILED => 'Failed',
                        Website::STATUS_PUBLISHED => 'Published',
                    ])
                    ->required()
                    ->default(Website::STATUS_DRAFT),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
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
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
