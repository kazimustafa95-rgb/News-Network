<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['slug'] = Str::slug((string) ($data['slug'] ?? $this->record->slug), '_');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
