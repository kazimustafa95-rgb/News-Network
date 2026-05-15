<?php

namespace App\Filament\Resources\UserSubmissionResource\Pages;

use App\Filament\Resources\UserSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListUserSubmissions extends ListRecords
{
    protected static string $resource = UserSubmissionResource::class;
}
