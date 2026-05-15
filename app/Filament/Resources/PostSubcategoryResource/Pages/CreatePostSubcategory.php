<?php

namespace App\Filament\Resources\PostSubcategoryResource\Pages;

use App\Filament\Resources\PostSubcategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePostSubcategory extends CreateRecord
{
    protected static string $resource = PostSubcategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return PostSubcategoryResource::normalizePayload($data);
    }
}
