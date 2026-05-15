<?php

namespace App\Models;

use App\Enums\NewsPostStatus;
use App\Enums\PostTopic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NewsPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'county_id',
        'author_id',
        'user_submission_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'post_category_id',
        'post_subcategory_id',
        'topic',
        'source_type',
        'status',
        'is_featured',
        'is_breaking',
        'published_at',
        'archive_at',
        'archived_at',
        'rejected_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'topic' => PostTopic::class,
            'status' => NewsPostStatus::class,
            'is_featured' => 'boolean',
            'is_breaking' => 'boolean',
            'published_at' => 'datetime',
            'archive_at' => 'datetime',
            'archived_at' => 'datetime',
            'rejected_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function postCategory(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function postSubcategory(): BelongsTo
    {
        return $this->belongsTo(PostSubcategory::class, 'post_subcategory_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(UserSubmission::class, 'user_submission_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(PostVideo::class);
    }

    public function archive(): HasOne
    {
        return $this->hasOne(PostArchive::class);
    }

    public static function ensureUniqueSlug(string $candidate, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($candidate);

        if ($baseSlug === '') {
            $baseSlug = 'news-post';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public static function resolveLegacyTopicFromTaxonomyIds(?int $categoryId, ?int $subcategoryId = null): string
    {
        $candidateSlugs = [];

        if ($subcategoryId) {
            $candidateSlugs[] = PostSubcategory::query()->whereKey($subcategoryId)->value('slug');
        }

        if ($categoryId) {
            $candidateSlugs[] = PostCategory::query()->whereKey($categoryId)->value('slug');
        }

        foreach (array_filter($candidateSlugs) as $slug) {
            if (PostTopic::tryFrom((string) $slug)) {
                return (string) $slug;
            }
        }

        return PostTopic::General->value;
    }
}
