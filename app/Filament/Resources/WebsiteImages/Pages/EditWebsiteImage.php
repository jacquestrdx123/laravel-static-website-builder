<?php

namespace App\Filament\Resources\WebsiteImages\Pages;

use App\Filament\Resources\WebsiteImages\WebsiteImageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteImage extends EditRecord
{
    protected static string $resource = WebsiteImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
