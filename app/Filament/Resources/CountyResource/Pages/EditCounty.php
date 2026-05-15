<?php

namespace App\Filament\Resources\CountyResource\Pages;

use App\Enums\CountyStatus;
use App\Filament\Resources\CountyResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditCounty extends EditRecord
{
    protected static string $resource = CountyResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : Str::slug((string) ($data['name'] ?? $this->record->name));

        if (($data['status'] ?? null) === CountyStatus::Active->value && blank($data['launch_date'] ?? null)) {
            $data['launch_date'] = now()->toDateString();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
