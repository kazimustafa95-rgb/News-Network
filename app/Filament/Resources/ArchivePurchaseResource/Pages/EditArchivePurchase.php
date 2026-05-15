<?php

namespace App\Filament\Resources\ArchivePurchaseResource\Pages;

use App\Filament\Resources\ArchivePurchaseResource;
use Filament\Resources\Pages\EditRecord;

class EditArchivePurchase extends EditRecord
{
    protected static string $resource = ArchivePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
