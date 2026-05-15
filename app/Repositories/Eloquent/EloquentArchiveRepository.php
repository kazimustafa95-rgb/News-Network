<?php

namespace App\Repositories\Eloquent;

use App\Enums\ArchivePurchaseStatus;
use App\Models\ArchivePurchase;
use App\Models\County;
use App\Models\NewsPost;
use App\Models\PostArchive;
use App\Models\User;
use App\Repositories\Contracts\ArchiveRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentArchiveRepository implements ArchiveRepositoryInterface
{
    public function paginateArchiveForCounty(County $county, string $archiveDate, array $filters = []): LengthAwarePaginator
    {
        return NewsPost::query()
            ->with(['videos', 'author.profile', 'archive', 'county'])
            ->where('county_id', $county->id)
            ->where('status', \App\Enums\NewsPostStatus::Archived)
            ->whereHas('archive', fn ($query) => $query->where('archive_date', $archiveDate))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%')
                        ->orWhere('body', 'like', '%'.$search.'%');
                });
            })
            ->latest('archived_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function userHasAccess(User $user, int $countyId, string $archiveDate): bool
    {
        return ArchivePurchase::query()
            ->where('user_id', $user->id)
            ->where('county_id', $countyId)
            ->whereDate('archive_date', $archiveDate)
            ->where('status', ArchivePurchaseStatus::Paid)
            ->exists();
    }

    public function recordPurchase(User $user, array $attributes): ArchivePurchase
    {
        return ArchivePurchase::updateOrCreate(
            [
                'user_id' => $user->id,
                'county_id' => $attributes['county_id'],
                'archive_date' => $attributes['archive_date'],
            ],
            $attributes + [
                'purchased_at' => now(),
            ],
        );
    }

    public function latestArchiveOfferForCountyDate(County $county, string $archiveDate): ?PostArchive
    {
        return PostArchive::query()
            ->where('county_id', $county->id)
            ->whereDate('archive_date', $archiveDate)
            ->latest('id')
            ->first();
    }
}
