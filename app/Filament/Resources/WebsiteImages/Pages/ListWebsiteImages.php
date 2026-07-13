<?php

namespace App\Filament\Resources\WebsiteImages\Pages;

use App\Filament\Resources\WebsiteImages\WebsiteImageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebsiteImages extends ListRecords
{
    protected static string $resource = WebsiteImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
