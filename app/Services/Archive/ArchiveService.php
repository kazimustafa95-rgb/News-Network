<?php

namespace App\Services\Archive;

use App\Enums\ArchivePurchaseStatus;
use App\Models\County;
use App\Models\User;
use App\Repositories\Contracts\ArchiveRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\NewsPostRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ArchiveService
{
    public function __construct(
        private readonly ArchiveRepositoryInterface $archives,
        private readonly LocationRepositoryInterface $locations,
        private readonly NewsPostRepositoryInterface $posts,
    ) {
    }

    public function index(array $filters = [], ?User $user = null): array
    {
        $county = $this->resolveCounty((int) ($filters['county_id'] ?? 0));
        $archiveDate = (string) ($filters['archive_date'] ?? now()->subDays(8)->toDateString());
        $posts = $this->archives->paginateArchiveForCounty($county, $archiveDate, $filters);
        $offer = $this->archives->latestArchiveOfferForCountyDate($county, $archiveDate);
        $hasAccess = $user ? $this->archives->userHasAccess($user, $county->id, $archiveDate) : false;

        return [
            'county' => $county,
            'archive_date' => $archiveDate,
            'posts' => $posts,
            'entitlement' => [
                'has_access' => $hasAccess,
                'purchase_required' => ! $hasAccess,
                'price_cents' => $offer?->price_cents,
                'currency' => $offer?->currency ?? config('community_will.archive.currency', 'USD'),
            ],
        ];
    }

    public function purchase(User $user, array $attributes)
    {
        return $this->archives->recordPurchase($user, $attributes + [
            'status' => ArchivePurchaseStatus::Paid->value,
            'currency' => $attributes['currency'] ?? config('community_will.archive.currency', 'USD'),
            'verified_at' => now(),
            'provider_payload' => array_filter([
                'purchase_token' => $attributes['purchase_token'] ?? null,
            ]),
        ]);
    }

    public function userHasArchiveAccess(User $user, int $countyId, string $archiveDate): bool
    {
        return $this->archives->userHasAccess($user, $countyId, $archiveDate);
    }

    private function resolveCounty(int $countyId): County
    {
        $county = $this->locations->findCountyById($countyId);

        if (! $county) {
            throw (new ModelNotFoundException())->setModel(County::class, [$countyId]);
        }

        return $county;
    }

    public function purchaseHistory(User $user, array $filters = [])
    {
        return $this->posts->paginatePurchasedForUser($user, $filters);
    }
}
