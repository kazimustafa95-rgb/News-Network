<?php

namespace App\Filament\Resources\PostArchiveResource\Pages;

use App\Filament\Resources\PostArchiveResource;
use Filament\Resources\Pages\EditRecord;

class EditPostArchive extends EditRecord
{
    protected static string $resource = PostArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
