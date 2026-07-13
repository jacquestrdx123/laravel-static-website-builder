<?php

namespace App\Filament\Resources\WebsiteImages\Schemas;

use App\Models\WebsiteImage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WebsiteImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('website_id')
                    ->relationship('website', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('path')
                    ->required(),
                TextInput::make('original_name')
                    ->required(),
                Select::make('type')
                    ->options([
                        WebsiteImage::TYPE_LOGO => 'Logo',
                        WebsiteImage::TYPE_FAVICON => 'Favicon',
                        WebsiteImage::TYPE_BANNER => 'Banner',
                        WebsiteImage::TYPE_GALLERY => 'Gallery',
                        WebsiteImage::TYPE_PRODUCT => 'Product',
                    ])
                    ->required()
                    ->default(WebsiteImage::TYPE_GALLERY),
                TextInput::make('mime_type')
                    ->required(),
                TextInput::make('sort')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('description')
                    ->maxLength(200),
            ]);
    }
}
