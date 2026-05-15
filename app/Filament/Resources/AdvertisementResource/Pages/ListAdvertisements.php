<?php

namespace App\Filament\Resources\AdvertisementResource\Pages;

use App\Filament\Resources\AdvertisementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdvertisements extends ListRecords
{
    protected static string $resource = AdvertisementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
