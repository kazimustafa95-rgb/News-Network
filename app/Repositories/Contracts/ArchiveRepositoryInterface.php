<?php

namespace App\Repositories\Contracts;

use App\Models\ArchivePurchase;
use App\Models\County;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ArchiveRepositoryInterface
{
    public function paginateArchiveForCounty(County $county, string $archiveDate, array $filters = []): LengthAwarePaginator;

    public function userHasAccess(User $user, int $countyId, string $archiveDate): bool;

    public function recordPurchase(User $user, array $attributes): ArchivePurchase;

    public function latestArchiveOfferForCountyDate(County $county, string $archiveDate): ?\App\Models\PostArchive;
}
