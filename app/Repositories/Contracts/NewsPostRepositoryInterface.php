<?php

namespace App\Repositories\Contracts;

use App\Models\County;
use App\Models\NewsPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface NewsPostRepositoryInterface
{
    public function findPublishedById(int $id): ?NewsPost;

    public function getFeaturedForCounty(County $county, int $limit): Collection;

    public function getBreakingForCounty(County $county, int $limit): Collection;

    public function paginateTimelineForCounty(County $county, array $filters = []): LengthAwarePaginator;

    public function paginateForAdmin(array $filters = []): LengthAwarePaginator;

    public function paginatePurchasedForUser(\App\Models\User $user, array $filters = []): LengthAwarePaginator;
}
