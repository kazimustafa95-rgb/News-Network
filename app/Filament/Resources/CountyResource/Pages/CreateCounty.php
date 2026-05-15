<?php

namespace App\Filament\Resources\CountyResource\Pages;

use App\Enums\CountyStatus;
use App\Filament\Resources\CountyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCounty extends CreateRecord
{
    protected static string $resource = CountyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : Str::slug((string) ($data['name'] ?? 'county'));

        if (($data['status'] ?? null) === CountyStatus::Active->value && blank($data['launch_date'] ?? null)) {
            $data['launch_date'] = now()->toDateString();
        }

        return $data;
    }
}
