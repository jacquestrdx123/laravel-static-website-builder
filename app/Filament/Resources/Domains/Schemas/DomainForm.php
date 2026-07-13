<?php

namespace App\Filament\Resources\Domains\Schemas;

use App\Models\Domain;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DomainForm
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
                Select::make('website_id')
                    ->relationship('website', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('domain')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->options([
                        Domain::STATUS_PENDING => 'Pending',
                        Domain::STATUS_ACTIVE => 'Active',
                        Domain::STATUS_EXPIRED => 'Expired',
                        Domain::STATUS_TRANSFERRED => 'Transferred',
                        Domain::STATUS_CANCELLED => 'Cancelled',
                        Domain::STATUS_FAILED => 'Failed',
                    ])
                    ->required(),
                TextInput::make('regperiod')
                    ->numeric()
                    ->required(),
                DateTimePicker::make('registered_at'),
                DateTimePicker::make('expires_at'),
                Toggle::make('auto_renew'),
                Toggle::make('id_protection'),
                Toggle::make('registrar_locked'),
                KeyValue::make('nameservers')
                    ->columnSpanFull(),
                KeyValue::make('contacts')
                    ->columnSpanFull(),
                KeyValue::make('meta')
                    ->columnSpanFull(),
            ]);
    }
}
