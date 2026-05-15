<?php

namespace App\Filament\Resources\NewsPostResource\Pages;

use App\Filament\Resources\NewsPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsPost extends CreateRecord
{
    protected static string $resource = NewsPostResource::class;

    protected array $mediaFiles = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->mediaFiles = array_values((array) ($data['media_files'] ?? []));
        unset($data['media_files']);

        $data = NewsPostResource::normalizePayload($data);
        $data['author_id'] ??= auth()->id();
        $data['source_type'] = 'admin_original';

        return $data;
    }

    protected function afterCreate(): void
    {
        NewsPostResource::syncUploadedMedia(
            $this->record,
            NewsPostResource::normalizeUploadedMediaState($this->mediaFiles),
        );
    }
}
