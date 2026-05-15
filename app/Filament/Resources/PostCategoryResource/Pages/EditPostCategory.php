<?php

namespace App\Filament\Resources\PostCategoryResource\Pages;

use App\Filament\Resources\PostCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPostCategory extends EditRecord
{
    protected static string $resource = PostCategoryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PostCategoryResource::normalizePayload($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
