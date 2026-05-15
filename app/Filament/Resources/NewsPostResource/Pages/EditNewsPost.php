<?php

namespace App\Filament\Resources\NewsPostResource\Pages;

use App\Filament\Resources\NewsPostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNewsPost extends EditRecord
{
    protected static string $resource = NewsPostResource::class;

    protected array $mediaFiles = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['media_files'] = NewsPostResource::existingMediaPaths($this->record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->mediaFiles = array_values((array) ($data['media_files'] ?? []));
        unset($data['media_files']);

        $data = NewsPostResource::normalizePayload($data, $this->record);
        $data['source_type'] = $this->record->source_type;

        return $data;
    }

    protected function afterSave(): void
    {
        $normalizedMedia = NewsPostResource::normalizeUploadedMediaState($this->mediaFiles);

        // Preserve existing media when the upload widget hydrates empty and the editor
        // only updates text fields on an already-published story.
        if ($normalizedMedia === [] && $this->record->videos()->exists()) {
            return;
        }

        NewsPostResource::syncUploadedMedia($this->record, $normalizedMedia);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
