<?php

namespace App\Filament\Resources\Websites\Schemas;

use App\Models\Website;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WebsiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
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
                KeyValue::make('settings')
                    ->columnSpanFull(),
                Textarea::make('error')
                    ->columnSpanFull()
                    ->rows(3),
                TextInput::make('custom_domain')
                    ->unique(ignoreRecord: true),
                DateTimePicker::make('generated_at'),
                DateTimePicker::make('published_at'),
            ]);
    }
}
