<?php

namespace App\Filament\Resources\AdvertisementResource\Pages;

use App\Filament\Resources\AdvertisementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdvertisement extends EditRecord
{
    protected static string $resource = AdvertisementResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['disk'] = config('community_will.media.ad_disk');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
