<?php

namespace App\Filament\Resources\PromoCodes\Schemas;

use App\Models\PromoCode;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PromoCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(64)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state)))
                    ->helperText('Stored uppercase. Letters and numbers recommended.'),
                Select::make('type')
                    ->options([
                        PromoCode::TYPE_CREDITS => 'Credits',
                        PromoCode::TYPE_CHECKOUT_DISCOUNT => 'Checkout discount',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('credits_amount')
                    ->label('Credits granted')
                    ->numeric()
                    ->minValue(1)
                    ->required(fn (Get $get): bool => $get('type') === PromoCode::TYPE_CREDITS)
                    ->visible(fn (Get $get): bool => $get('type') === PromoCode::TYPE_CREDITS),
                Select::make('discount_type')
                    ->options([
                        PromoCode::DISCOUNT_PERCENT => 'Percent off',
                        PromoCode::DISCOUNT_FIXED => 'Fixed ZAR off',
                    ])
                    ->required(fn (Get $get): bool => $get('type') === PromoCode::TYPE_CHECKOUT_DISCOUNT)
                    ->visible(fn (Get $get): bool => $get('type') === PromoCode::TYPE_CHECKOUT_DISCOUNT)
                    ->live(),
                TextInput::make('discount_value')
                    ->numeric()
                    ->minValue(0.01)
                    ->required(fn (Get $get): bool => $get('type') === PromoCode::TYPE_CHECKOUT_DISCOUNT)
                    ->visible(fn (Get $get): bool => $get('type') === PromoCode::TYPE_CHECKOUT_DISCOUNT)
                    ->helperText(fn (Get $get): string => $get('discount_type') === PromoCode::DISCOUNT_PERCENT
                        ? 'Percent off the pack price (1–100).'
                        : 'ZAR amount deducted from the pack price.'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                DateTimePicker::make('expires_at')
                    ->label('Expires at'),
                TextInput::make('max_redemptions')
                    ->label('Max redemptions')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Leave empty for unlimited redemptions.'),
                TextInput::make('times_redeemed')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
            ]);
    }
}
