<?php

namespace App\Services\Feed;

use App\Enums\ArchivePurchaseStatus;
use App\Enums\CountyStatus;
use App\Enums\NewsPostStatus;
use App\Enums\AdvertisementStatus;
use App\Models\Advertisement;
use App\Models\County;
use App\Models\NewsPost;
use App\Models\User;
use App\Repositories\Contracts\ArchiveRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\NewsPostRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FeedService
{
    public function __construct(
        private readonly NewsPostRepositoryInterface $posts,
        private readonly LocationRepositoryInterface $locations,
        private readonly ArchiveRepositoryInterface $archives,
    ) {
    }

    public function getFeed(array $filters = []): array
    {
        $countyId = isset($filters['county_id']) ? (int) $filters['county_id'] : null;
        $county = $countyId
            ? $this->locations->findCountyById($countyId)
            : County::query()->where('status', CountyStatus::Active)->orderBy('name')->first();

        if (! $county || $county->status !== CountyStatus::Active) {
            throw (new ModelNotFoundException())->setModel(County::class, [$countyId]);
        }

        $featured = $this->posts->getFeaturedForCounty($county, (int) config('community_will.feed.featured_limit', 3));
        $breaking = $this->posts->getBreakingForCounty($county, (int) config('community_will.feed.breaking_limit', 6));
        $timeline = $this->posts->paginateTimelineForCounty($county, [
            'topic' => $filters['topic'] ?? null,
            'category_id' => $filters['category_id'] ?? null,
            'subcategory_id' => $filters['subcategory_id'] ?? null,
            'search' => $filters['search'] ?? null,
            'per_page' => $filters['per_page'] ?? config('community_will.feed.timeline_per_page', 10),
        ]);
        $ads = $county->advertisements()
            ->where('status', AdvertisementStatus::Active->value)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        return [
            'county' => $county,
            'featured' => $featured,
            'breaking' => $breaking,
            'timeline' => $timeline,
            'ads' => $ads,
            'ad_interval' => (int) config('community_will.feed.ad_interval', 5),
        ];
    }

    public function getAvailableCounties(array $filters = [])
    {
        return County::query()
            ->whereIn('status', [CountyStatus::Active, CountyStatus::ComingSoon])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->with('region.country')
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();
    }

    public function getPost(int $postId, ?User $viewer = null): NewsPost
    {
        $post = $this->posts->findPublishedById($postId);

        if (! $post || ! in_array($post->status, [NewsPostStatus::Published, NewsPostStatus::Archived], true)) {
            throw (new ModelNotFoundException())->setModel(NewsPost::class, [$postId]);
        }

        if ($post->status === NewsPostStatus::Archived) {
            $archiveDate = optional($post->archive?->archive_date)->toDateString();

            if (! $viewer || ! $archiveDate || ! $this->archives->userHasAccess($viewer, $post->county_id, $archiveDate)) {
                throw new AuthorizationException('Archive purchase required to view this story.');
            }
        }

        return $post;
    }
}
