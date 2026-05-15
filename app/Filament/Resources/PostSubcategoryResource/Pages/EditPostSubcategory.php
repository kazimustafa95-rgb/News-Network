<?php

namespace App\Filament\Resources\PostSubcategoryResource\Pages;

use App\Filament\Resources\PostSubcategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPostSubcategory extends EditRecord
{
    protected static string $resource = PostSubcategoryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PostSubcategoryResource::normalizePayload($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
