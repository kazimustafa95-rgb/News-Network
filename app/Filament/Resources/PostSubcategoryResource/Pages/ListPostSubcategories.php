<?php

namespace App\Filament\Resources\PostSubcategoryResource\Pages;

use App\Filament\Resources\PostSubcategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostSubcategories extends ListRecords
{
    protected static string $resource = PostSubcategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
