<?php

namespace App\Repositories\Eloquent;

use App\Enums\ArchivePurchaseStatus;
use App\Enums\NewsPostStatus;
use App\Models\County;
use App\Models\NewsPost;
use App\Models\User;
use App\Repositories\Contracts\NewsPostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentNewsPostRepository implements NewsPostRepositoryInterface
{
    public function findPublishedById(int $id): ?NewsPost
    {
        return NewsPost::query()
            ->with(['county', 'author.profile', 'videos', 'archive', 'postCategory', 'postSubcategory'])
            ->whereKey($id)
            ->first();
    }

    public function getFeaturedForCounty(County $county, int $limit): Collection
    {
        return NewsPost::query()
            ->with(['videos', 'author.profile', 'postCategory', 'postSubcategory'])
            ->where('county_id', $county->id)
            ->where('status', NewsPostStatus::Published)
            ->where('is_featured', true)
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function getBreakingForCounty(County $county, int $limit): Collection
    {
        return NewsPost::query()
            ->with(['videos', 'author.profile', 'postCategory', 'postSubcategory'])
            ->where('county_id', $county->id)
            ->where('status', NewsPostStatus::Published)
            ->where('is_breaking', true)
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function paginateTimelineForCounty(County $county, array $filters = []): LengthAwarePaginator
    {
        return NewsPost::query()
            ->with(['videos', 'author.profile', 'county', 'postCategory', 'postSubcategory'])
            ->where('county_id', $county->id)
            ->where('status', NewsPostStatus::Published)
            ->when($filters['topic'] ?? null, fn ($query, $topic) => $query->where('topic', $topic))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('post_category_id', $categoryId))
            ->when($filters['subcategory_id'] ?? null, fn ($query, $subcategoryId) => $query->where('post_subcategory_id', $subcategoryId))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%')
                        ->orWhere('body', 'like', '%'.$search.'%');
                });
            })
            ->latest('published_at')
            ->paginate((int) ($filters['per_page'] ?? config('community_will.feed.timeline_per_page', 10)));
    }

    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        return NewsPost::query()
            ->with(['county', 'author', 'postCategory', 'postSubcategory'])
            ->when($filters['county_id'] ?? null, fn ($query, $countyId) => $query->where('county_id', $countyId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['topic'] ?? null, fn ($query, $topic) => $query->where('topic', $topic))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('post_category_id', $categoryId))
            ->when($filters['subcategory_id'] ?? null, fn ($query, $subcategoryId) => $query->where('post_subcategory_id', $subcategoryId))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('title', 'like', '%'.$search.'%'))
            ->latest('created_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function paginatePurchasedForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return NewsPost::query()
            ->select('news_posts.*', 'archive_purchases.purchased_at as entitlement_purchased_at')
            ->with(['videos', 'author.profile', 'county', 'archive', 'postCategory', 'postSubcategory'])
            ->join('post_archives', 'post_archives.news_post_id', '=', 'news_posts.id')
            ->join('archive_purchases', function ($join) use ($user) {
                $join->on('archive_purchases.county_id', '=', 'post_archives.county_id')
                    ->on('archive_purchases.archive_date', '=', 'post_archives.archive_date')
                    ->where('archive_purchases.user_id', '=', $user->id)
                    ->where('archive_purchases.status', '=', ArchivePurchaseStatus::Paid->value);
            })
            ->where('news_posts.status', NewsPostStatus::Archived)
            ->when($filters['county_id'] ?? null, fn ($query, $countyId) => $query->where('news_posts.county_id', $countyId))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('news_posts.post_category_id', $categoryId))
            ->when($filters['subcategory_id'] ?? null, fn ($query, $subcategoryId) => $query->where('news_posts.post_subcategory_id', $subcategoryId))
            ->when($filters['archive_date'] ?? null, fn ($query, $archiveDate) => $query->whereDate('post_archives.archive_date', $archiveDate))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('news_posts.title', 'like', '%'.$search.'%')
                        ->orWhere('news_posts.excerpt', 'like', '%'.$search.'%')
                        ->orWhere('news_posts.body', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('archive_purchases.purchased_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
