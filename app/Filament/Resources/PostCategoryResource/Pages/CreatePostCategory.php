<?php

namespace App\Filament\Resources\PostCategoryResource\Pages;

use App\Filament\Resources\PostCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePostCategory extends CreateRecord
{
    protected static string $resource = PostCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return PostCategoryResource::normalizePayload($data);
    }
}
