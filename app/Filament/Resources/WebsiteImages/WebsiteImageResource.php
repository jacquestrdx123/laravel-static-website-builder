<?php

namespace App\Filament\Resources\WebsiteImages;

use App\Filament\Resources\WebsiteImages\Pages\CreateWebsiteImage;
use App\Filament\Resources\WebsiteImages\Pages\EditWebsiteImage;
use App\Filament\Resources\WebsiteImages\Pages\ListWebsiteImages;
use App\Filament\Resources\WebsiteImages\Schemas\WebsiteImageForm;
use App\Filament\Resources\WebsiteImages\Tables\WebsiteImagesTable;
use App\Models\WebsiteImage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WebsiteImageResource extends Resource
{
    protected static ?string $model = WebsiteImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'original_name';

    public static function form(Schema $schema): Schema
    {
        return WebsiteImageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebsiteImagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebsiteImages::route('/'),
            'create' => CreateWebsiteImage::route('/create'),
            'edit' => EditWebsiteImage::route('/{record}/edit'),
        ];
    }
}
